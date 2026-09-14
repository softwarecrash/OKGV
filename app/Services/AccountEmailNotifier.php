<?php

namespace App\Services;

use App\Enums\AnnouncementAudience;
use App\Enums\EmailNotificationTopic;
use App\Models\Announcement;
use App\Models\Document;
use App\Models\DunningNotice;
use App\Models\GardenInspectionFinding;
use App\Models\Invoice;
use App\Models\MemberAssembly;
use App\Models\MeterReadingSubmission;
use App\Models\Poll;
use App\Models\RegistrationRequest;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkEvent;
use App\Models\WorkHourSubmission;
use App\Notifications\AccountEmailNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

final class AccountEmailNotifier
{
    public function registrationRequested(RegistrationRequest $request): void
    {
        $this->send(
            $this->eligibleUsers()->filter(fn (User $user): bool => $user->canReviewTenantRegistrations()),
            EmailNotificationTopic::RegistrationRequests,
            'Neue Registrierungsanfrage',
            "Eine neue Zugangsanfrage von {$request->full_name} ({$request->email}) wartet auf Prüfung und Freigabe.",
            'Registrierungsanfragen öffnen',
            route('registration-requests.index'),
        );
    }

    public function announcementPublished(Announcement $announcement): void
    {
        $users = $this->eligibleUsers()->filter(function (User $user) use ($announcement): bool {
            return match ($announcement->audience) {
                AnnouncementAudience::Public, AnnouncementAudience::All => true,
                AnnouncementAudience::Tenants => $this->isCurrentTenant($user),
                AnnouncementAudience::Roles => in_array($user->role->value, $announcement->roles ?? [], true),
            };
        });

        $this->send(
            $users,
            EmailNotificationTopic::Announcements,
            'Neuer Beitrag: '.$announcement->title,
            'Am Schwarzen Brett wurde ein neuer Beitrag für dich veröffentlicht.',
            'Beitrag öffnen',
            route('announcements.show', $announcement),
        );
    }

    public function workEventCreated(WorkEvent $event): void
    {
        $this->send(
            $this->currentTenantUsers(),
            EmailNotificationTopic::WorkEvents,
            'Neuer Arbeitseinsatz: '.$event->title,
            'Ein neuer Arbeitseinsatz wurde geplant.',
            'Arbeitseinsatz öffnen',
            route('work-events.show', $event),
        );
    }

    public function gardenFindingCreated(GardenInspectionFinding $finding): void
    {
        $users = $finding->parcel->tenancies()->activeOn()->with('member.user')->get()
            ->map(fn ($tenancy) => $tenancy->member?->user)
            ->filter(fn ($user) => $user instanceof User);

        $this->send(
            $users,
            EmailNotificationTopic::GardenInspections,
            'Neue Feststellung zu deiner Parzelle',
            'Bei einer Gartenbegehung wurde eine Feststellung zu deiner Parzelle erfasst.',
            'Feststellung öffnen',
            route('garden-inspections.show', $finding->garden_inspection_id),
        );
    }

    public function pollPublished(Poll $poll): void
    {
        $users = $poll->participations()->with('user')->get()->pluck('user')->filter();

        $this->send(
            $users,
            EmailNotificationTopic::Polls,
            'Neue Umfrage: '.$poll->title,
            'Du wurdest zu einer Umfrage oder Terminabfrage eingeladen.',
            'Umfrage öffnen',
            route('polls.show', $poll),
        );
    }

    public function memberAssemblyPublished(MemberAssembly $assembly): void
    {
        $this->send(
            $this->eligibleUsers()->filter(fn (User $user): bool => $user->member()->exists()),
            EmailNotificationTopic::MemberAssemblies,
            'Mitgliederversammlung veröffentlicht',
            'Eine Mitgliederversammlung wurde veröffentlicht.',
            'Versammlung öffnen',
            route('member-assemblies.show', $assembly),
        );
    }

    public function invoiceApproved(Invoice $invoice): void
    {
        $this->send($this->invoiceUsers($invoice), EmailNotificationTopic::Finance, 'Neue Rechnung: '.$invoice->invoice_number, 'Eine neue Rechnung wurde freigegeben und steht in deinem Pächterportal bereit.', 'Pächterportal öffnen', route('tenant-portal.index'));
    }

    public function paymentSettled(Invoice $invoice): void
    {
        $this->send($this->invoiceUsers($invoice), EmailNotificationTopic::Finance, 'Zahlung bestätigt: '.$invoice->invoice_number, 'Der Zahlungseingang zu deiner Rechnung wurde erfasst.', 'Pächterportal öffnen', route('tenant-portal.index'));
    }

    public function paymentReturned(Invoice $invoice): void
    {
        $this->send($this->invoiceUsers($invoice), EmailNotificationTopic::Finance, 'Rücklastschrift: '.$invoice->invoice_number, 'Die Lastschrift zu deiner Rechnung wurde zurückgegeben. Bitte prüfe die Rechnung im Pächterportal.', 'Pächterportal öffnen', route('tenant-portal.index'));
    }

    public function dunningNoticeIssued(DunningNotice $notice): void
    {
        $this->send($this->invoiceUsers($notice->invoice), EmailNotificationTopic::Finance, "Mahnung Stufe {$notice->level}: {$notice->invoice_number}", 'Zu einer offenen Rechnung wurde eine Mahnung erstellt.', 'Pächterportal öffnen', route('tenant-portal.index'));
    }

    public function documentPublished(Document $document): void
    {
        $document->load('member.user', 'parcel.tenancies.member.user');
        $users = collect([$document->member?->user])
            ->merge($document->parcel?->tenancies()->activeOn()->with('member.user')->get()->map(fn ($tenancy) => $tenancy->member?->user) ?? []);

        $this->send($users, EmailNotificationTopic::Documents, 'Neues Dokument: '.$document->title, 'Ein für dich bestimmtes Dokument wurde freigegeben.', 'Dokumente öffnen', route('tenant-portal.documents'));
    }

    public function meterReadingReviewed(MeterReadingSubmission $submission): void
    {
        $this->send([$submission->submitter()->first()], EmailNotificationTopic::Submissions, 'Zählerstand '.$submission->status->label(), 'Deine Zählerstandsmeldung wurde '.$submission->status->label().'.'.($submission->review_note ? ' Hinweis: '.$submission->review_note : ''), 'Meldung öffnen', route('meter-reading-submissions.index', ['own' => 1]));
    }

    public function workHourSubmissionReviewed(WorkHourSubmission $submission): void
    {
        $this->send([$submission->submitter()->first()], EmailNotificationTopic::Submissions, 'Arbeitsstunden '.$submission->status->label(), 'Deine Arbeitsstundenmeldung wurde '.$submission->status->label().'.'.($submission->review_note ? ' Hinweis: '.$submission->review_note : ''), 'Meldung öffnen', route('work-hour-submissions.index', ['own' => 1]));
    }

    public function taskAssigned(Task $task): void
    {
        $this->send([$task->assignee()->first()], EmailNotificationTopic::Tasks, 'Neue Aufgabe: '.$task->title, 'Dir wurde eine Aufgabe zugewiesen.', 'Aufgabe öffnen', route('tasks.show', $task));
    }

    /** @param Collection<int, User>|iterable<User> $users */
    private function send(iterable $users, EmailNotificationTopic $topic, string $subject, string $intro, string $actionText, string $url): void
    {
        collect($users)->filter(fn ($user): bool => $user instanceof User)
            ->unique('id')
            ->filter(fn (User $user): bool => $this->isEligible($user))
            ->filter(fn (User $user): bool => $user->wantsEmailNotification($topic))
            ->each(function (User $user) use ($topic, $subject, $intro, $actionText, $url): void {
                try {
                    $user->notify(new AccountEmailNotification($subject, $intro, $actionText, $url));
                } catch (Throwable $exception) {
                    Log::warning('Account notification email could not be sent.', [
                        'topic' => $topic->value,
                        'user_id' => $user->id,
                        'exception' => $exception->getMessage(),
                    ]);
                }
            });
    }

    /** @return Collection<int, User> */
    private function eligibleUsers(): Collection
    {
        return User::query()->whereNotNull('email_verified_at')->get()
            ->reject(fn (User $user): bool => $user->hasPendingRegistrationApproval());
    }

    private function isEligible(User $user): bool
    {
        return $user->email_verified_at !== null && ! $user->hasPendingRegistrationApproval();
    }

    /** @return Collection<int, User> */
    private function invoiceUsers(Invoice $invoice): Collection
    {
        $invoice->load('member.user', 'recipients.member.user');

        return collect([$invoice->member?->user])
            ->merge($invoice->recipients->map(fn ($recipient) => $recipient->member?->user));
    }

    /** @return Collection<int, User> */
    private function currentTenantUsers(): Collection
    {
        return $this->eligibleUsers()->filter(fn (User $user): bool => $this->isCurrentTenant($user));
    }

    private function isCurrentTenant(User $user): bool
    {
        return $user->member?->parcelTenancies()->activeOn()->exists() ?? false;
    }
}
