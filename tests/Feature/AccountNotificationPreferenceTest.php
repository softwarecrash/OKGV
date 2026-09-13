<?php

namespace Tests\Feature;

use App\Enums\AnnouncementAudience;
use App\Enums\EmailNotificationTopic;
use App\Models\Announcement;
use App\Models\Member;
use App\Models\Parcel;
use App\Models\ParcelTenant;
use App\Models\User;
use App\Notifications\AccountEmailNotification;
use App\Services\AccountEmailNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountNotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_relevant_preferences_default_to_enabled_and_can_be_disabled(): void
    {
        $tenant = $this->currentTenant();

        $this->actingAs($tenant)
            ->get(route('account.notifications.edit'))
            ->assertOk()
            ->assertSee('Beiträge am Schwarzen Brett')
            ->assertSee('Neue Arbeitseinsätze')
            ->assertSee('Gartenbegehungen und Feststellungen');

        $this->put(route('account.notifications.update'), [
            'preferences' => [
                EmailNotificationTopic::Announcements->value => '0',
                EmailNotificationTopic::WorkEvents->value => '1',
                EmailNotificationTopic::GardenInspections->value => '1',
            ],
        ])->assertRedirect(route('account.notifications.edit'));

        $tenant->refresh();
        $this->assertFalse($tenant->wantsEmailNotification(EmailNotificationTopic::Announcements));
        $this->assertTrue($tenant->wantsEmailNotification(EmailNotificationTopic::WorkEvents));
    }

    public function test_registration_notifies_eligible_reviewers_by_default(): void
    {
        Notification::fake();
        $reviewer = User::factory()->administrator()->create();

        $this->post(route('tenant-registration.store'), [
            'first_name' => 'Neue',
            'last_name' => 'Anfrage',
            'email' => 'neue-anfrage@example.test',
            'password' => 'SicheresPasswort123',
            'password_confirmation' => 'SicheresPasswort123',
        ])->assertRedirect(route('login'));

        Notification::assertSentTo($reviewer, AccountEmailNotification::class);
    }

    public function test_announcement_email_respects_audience_and_opt_out(): void
    {
        Notification::fake();
        $recipient = $this->currentTenant();
        $optedOut = $this->currentTenant();
        $optedOut->update([
            'notification_preferences' => [EmailNotificationTopic::Announcements->value => false],
        ]);
        $publisher = User::factory()->administrator()->create();
        $announcement = Announcement::create([
            'title' => 'Wasser wird abgestellt',
            'body' => 'Hinweis für die Pächter.',
            'audience' => AnnouncementAudience::Tenants,
            'roles' => [],
            'starts_at' => now(),
            'published_at' => now(),
            'created_by' => $publisher->id,
        ]);

        app(AccountEmailNotifier::class)->announcementPublished($announcement);

        Notification::assertSentTo($recipient, AccountEmailNotification::class);
        Notification::assertNotSentTo($optedOut, AccountEmailNotification::class);
        Notification::assertNotSentTo($publisher, AccountEmailNotification::class);
    }

    private function currentTenant(): User
    {
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id, 'joined_at' => now()->subYear()]);
        $parcel = Parcel::factory()->create();
        ParcelTenant::factory()->create([
            'parcel_id' => $parcel->id,
            'member_id' => $member->id,
            'starts_at' => now()->subMonth(),
        ]);

        return $user;
    }
}
