<?php

namespace Tests\Feature;

use App\Enums\PrivacyErasureStatus;
use App\Models\Member;
use App\Models\PrivacyErasureRequest;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_correct_a_members_login_email_and_resend_verification(): void
    {
        Notification::fake();
        $administrator = User::factory()->administrator()->create();
        $account = User::factory()->create([
            'email' => 'falsch@example.test',
            'email_verified_at' => now(),
        ]);
        $member = Member::factory()->create(['user_id' => $account->id]);

        $this->actingAs($administrator)
            ->put(route('user-accounts.email.update', $account), ['email' => 'richtig@example.test'])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $account->id,
            'email' => 'richtig@example.test',
            'email_verified_at' => null,
        ]);
        $this->assertDatabaseHas('members', ['id' => $member->id, 'user_id' => $account->id]);
        Notification::assertSentTo($account->fresh(), VerifyEmailNotification::class);
    }

    public function test_administrator_can_find_unlinked_accounts_for_correction_or_removal(): void
    {
        $administrator = User::factory()->administrator()->create();
        $unlinked = User::factory()->create(['email' => 'alt@example.test']);
        $linked = User::factory()->create(['email' => 'verbunden@example.test']);
        Member::factory()->create(['user_id' => $linked->id]);

        $this->actingAs($administrator)
            ->get(route('user-accounts.index'))
            ->assertOk()
            ->assertSee('alt@example.test')
            ->assertDontSee('verbunden@example.test');
    }

    public function test_administrator_can_remove_an_unused_member_login_without_deleting_the_member(): void
    {
        $administrator = User::factory()->administrator()->create([
            'password' => Hash::make('Admin-Test-123!'),
        ]);
        $account = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $account->id]);

        $this->actingAs($administrator)
            ->delete(route('user-accounts.destroy', $account), [
                'current_password' => 'Admin-Test-123!',
                'confirmation' => 'KONTO LÖSCHEN',
            ])
            ->assertRedirect(route('members.index'));

        $this->assertDatabaseMissing('users', ['id' => $account->id]);
        $this->assertDatabaseHas('members', ['id' => $member->id, 'user_id' => null]);
    }

    public function test_administrator_can_withdraw_an_accidental_open_privacy_erasure_request(): void
    {
        $administrator = User::factory()->administrator()->create();
        $member = Member::factory()->create();
        $request = PrivacyErasureRequest::create([
            'member_id' => $member->id,
            'requested_by' => $administrator->id,
            'status' => PrivacyErasureStatus::Pending,
            'requested_at' => now(),
        ]);

        $this->actingAs($administrator)
            ->post(route('privacy-erasure-requests.cancel', $request))
            ->assertRedirect(route('privacy.index'));

        $this->assertDatabaseHas('privacy_erasure_requests', [
            'id' => $request->id,
            'status' => PrivacyErasureStatus::Rejected->value,
            'review_note' => 'Löschanfrage wurde zurückgezogen.',
        ]);
    }
}
