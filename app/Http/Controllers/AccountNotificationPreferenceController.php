<?php

namespace App\Http\Controllers;

use App\Enums\EmailNotificationTopic;
use App\Http\Requests\AccountNotificationPreferenceRequest;
use App\Models\Task;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccountNotificationPreferenceController extends Controller
{
    public function edit(): View
    {
        $user = request()->user();

        return view('account.notifications', [
            'topics' => collect(EmailNotificationTopic::cases())->filter(fn (EmailNotificationTopic $topic): bool => match ($topic) {
                EmailNotificationTopic::RegistrationRequests => $user->canReviewTenantRegistrations(),
                EmailNotificationTopic::WorkEvents, EmailNotificationTopic::GardenInspections => $user->member?->parcelTenancies()->activeOn()->exists() ?? false,
                EmailNotificationTopic::Polls, EmailNotificationTopic::MemberAssemblies => $user->member()->exists(),
                EmailNotificationTopic::Finance, EmailNotificationTopic::Documents, EmailNotificationTopic::Submissions => $user->hasTenantAccess(),
                EmailNotificationTopic::Tasks => $user->can('viewAny', Task::class),
                EmailNotificationTopic::Announcements => true,
            }),
            'user' => $user,
        ]);
    }

    public function update(AccountNotificationPreferenceRequest $request): RedirectResponse
    {
        $user = $request->user();
        $allowed = $this->availableTopics($user);
        $values = array_intersect_key($request->preferenceValues(), array_flip($allowed));
        $user->forceFill([
            'notification_preferences' => [...($user->notification_preferences ?? []), ...$values],
        ])->save();

        AuditLogger::log('user.notification_preferences.updated', $user, $user, [
            'topics' => array_keys($values),
        ]);

        return redirect()->route('account.notifications.edit')->with('status', 'Deine E-Mail-Benachrichtigungen wurden gespeichert.');
    }

    /** @return list<string> */
    private function availableTopics($user): array
    {
        return collect(EmailNotificationTopic::cases())->filter(fn (EmailNotificationTopic $topic): bool => match ($topic) {
            EmailNotificationTopic::RegistrationRequests => $user->canReviewTenantRegistrations(),
            EmailNotificationTopic::WorkEvents, EmailNotificationTopic::GardenInspections => $user->member?->parcelTenancies()->activeOn()->exists() ?? false,
            EmailNotificationTopic::Polls, EmailNotificationTopic::MemberAssemblies => $user->member()->exists(),
            EmailNotificationTopic::Finance, EmailNotificationTopic::Documents, EmailNotificationTopic::Submissions => $user->hasTenantAccess(),
            EmailNotificationTopic::Tasks => $user->can('viewAny', Task::class),
            EmailNotificationTopic::Announcements => true,
        })->map(fn (EmailNotificationTopic $topic): string => $topic->value)->all();
    }
}
