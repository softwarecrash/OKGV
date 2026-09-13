<?php

namespace App\Services;

use App\Enums\AnnouncementAudience;
use App\Enums\EmailNotificationTopic;
use App\Models\Announcement;
use App\Models\GardenInspectionFinding;
use App\Models\MemberAssembly;
use App\Models\Poll;
use App\Models\RegistrationRequest;
use App\Models\User;
use App\Models\WorkEvent;
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
            'Eine neue Zugangsanfrage wartet auf Prüfung und Freigabe.',
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

    /** @param Collection<int, User>|iterable<User> $users */
    private function send(iterable $users, EmailNotificationTopic $topic, string $subject, string $intro, string $actionText, string $url): void
    {
        collect($users)->filter(fn ($user): bool => $user instanceof User)
            ->unique('id')
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
