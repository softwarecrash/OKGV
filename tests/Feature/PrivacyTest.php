<?php

namespace Tests\Feature;

use App\Enums\InvoicePaymentStatus;
use App\Enums\MemberStatus;
use App\Enums\PrivacyErasureStatus;
use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MemberPrivacySetting;
use App\Models\Parcel;
use App\Models\ParcelTenant;
use App\Models\PrivacyErasureRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_sharing_is_opt_in_and_visible_only_to_current_co_tenants(): void
    {
        $parcel = Parcel::factory()->create();
        [$firstUser, $firstMember] = $this->tenantWithMember('erste@example.test');
        [$secondUser, $secondMember] = $this->tenantWithMember('zweite@example.test');
        [, $outsiderMember] = $this->tenantWithMember('aussen@example.test');

        foreach ([$firstMember, $secondMember] as $member) {
            ParcelTenant::factory()->create([
                'parcel_id' => $parcel->id,
                'member_id' => $member->id,
                'starts_at' => today()->subYear(),
                'ends_at' => null,
            ]);
        }

        $this->actingAs($firstUser)
            ->get(route('privacy.index'))
            ->assertOk()
            ->assertDontSee($secondMember->email);

        $this->actingAs($secondUser)
            ->put(route('privacy.settings.update'), [
                'share_name' => '1',
                'share_email' => '1',
                'share_phone' => '0',
                'share_mobile' => '0',
                'share_address' => '0',
            ])
            ->assertRedirect(route('privacy.index'));

        $this->actingAs($firstUser)
            ->get(route('privacy.index'))
            ->assertOk()
            ->assertSee($secondMember->full_name)
            ->assertSee($secondMember->email)
            ->assertDontSee($outsiderMember->email);

        $this->actingAs($secondUser)
            ->put(route('privacy.settings.update'), [
                'share_name' => '0',
                'share_email' => '0',
                'share_phone' => '0',
                'share_mobile' => '0',
                'share_address' => '0',
            ]);

        $this->assertDatabaseHas('member_privacy_settings', [
            'member_id' => $secondMember->id,
            'share_name' => false,
            'share_email' => false,
            'consented_at' => null,
        ]);
    }

    public function test_tenant_can_export_only_own_personal_data(): void
    {
        [$user, $member] = $this->tenantWithMember('own@example.test');
        [, $otherMember] = $this->tenantWithMember('other@example.test');

        $response = $this->actingAs($user)
            ->get(route('privacy.export', $member));

        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/json; charset=UTF-8');
        $this->assertStringContainsString('own@example.test', $response->streamedContent());

        $this->actingAs($user)
            ->get(route('privacy.export', $otherMember))
            ->assertForbidden();
    }

    public function test_board_member_needs_explicit_privacy_permission(): void
    {
        $board = User::factory()->create([
            'role' => UserRole::Board,
            'permissions' => [UserPermission::ViewAllMasterData->value],
        ]);
        $member = Member::factory()->create();

        $this->actingAs($board)
            ->get(route('privacy.export', $member))
            ->assertForbidden();

        $board->update([
            'permissions' => [
                UserPermission::ViewAllMasterData->value,
                UserPermission::ManagePrivacy->value,
            ],
        ]);

        $this->actingAs($board->fresh())
            ->get(route('privacy.export', $member))
            ->assertOk();
    }

    public function test_erasure_review_is_blocked_while_retention_reasons_exist(): void
    {
        $administrator = User::factory()->administrator()->create();
        [$tenant, $member] = $this->tenantWithMember('active@example.test');
        $erasureRequest = PrivacyErasureRequest::create([
            'member_id' => $member->id,
            'requested_by' => $tenant->id,
            'status' => PrivacyErasureStatus::Pending,
            'requested_at' => now(),
        ]);

        $this->actingAs($administrator)
            ->post(route('privacy-erasure-requests.review', $erasureRequest), [
                'review_note' => 'Automatische Prüfung',
            ])
            ->assertRedirect(route('privacy.index'));

        $erasureRequest->refresh();
        $this->assertSame(PrivacyErasureStatus::Blocked, $erasureRequest->status);
        $this->assertNotEmpty($erasureRequest->blockers);
    }

    public function test_administrator_can_pseudonymize_eligible_member_after_review(): void
    {
        config()->set('privacy.retention_years', 1);
        $administrator = User::factory()->administrator()->create([
            'password' => 'Admin-Test-123!',
        ]);
        [$tenant, $member] = $this->tenantWithMember('old@example.test', [
            'status' => MemberStatus::Archived,
            'left_at' => today()->subYears(2),
            'archived_at' => now()->subYears(2),
        ]);
        MemberPrivacySetting::create([
            'member_id' => $member->id,
            'share_name' => true,
            'share_email' => true,
            'consented_at' => now(),
        ]);
        $parcel = Parcel::factory()->create();
        $tenancy = ParcelTenant::factory()->create([
            'parcel_id' => $parcel->id,
            'member_id' => $member->id,
            'starts_at' => today()->subYears(3),
            'ends_at' => today()->subYears(2),
            'notes' => 'Enthält personenbezogene Übergabenotizen.',
        ]);
        DB::table('registration_requests')->insert([
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'email' => $member->email,
            'parcel_id' => $parcel->id,
            'parcel_number' => $parcel->parcel_number,
            'password' => Hash::make('old-password'),
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $erasureRequest = PrivacyErasureRequest::create([
            'member_id' => $member->id,
            'requested_by' => $tenant->id,
            'status' => PrivacyErasureStatus::Ready,
            'requested_at' => now(),
            'reviewed_by' => $administrator->id,
            'reviewed_at' => now(),
        ]);

        $this->actingAs($administrator)
            ->post(route('privacy-erasure-requests.anonymize', $erasureRequest), [
                'current_password' => 'Admin-Test-123!',
                'confirmation' => 'PSEUDONYMISIEREN',
            ])
            ->assertRedirect(route('privacy.index'));

        $member->refresh();
        $tenant->refresh();
        $erasureRequest->refresh();

        $this->assertNull($member->user_id);
        $this->assertNull($member->email);
        $this->assertStringStartsWith('ANON-', $member->member_number);
        $this->assertStringContainsString('@invalid.local', $tenant->email);
        $this->assertFalse(Hash::check('password', $tenant->password));
        $this->assertSame(PrivacyErasureStatus::Completed, $erasureRequest->status);
        $this->assertNull($tenancy->fresh()->notes);
        $this->assertDatabaseHas('registration_requests', [
            'parcel_id' => $parcel->id,
            'first_name' => 'Anonymisiert',
            'email' => "anonymized-registration-{$member->id}@invalid.local",
            'password' => null,
        ]);
        $this->assertDatabaseHas('member_privacy_settings', [
            'member_id' => $member->id,
            'share_name' => false,
            'share_email' => false,
        ]);
    }

    public function test_unpaid_invoice_blocks_pseudonymization_even_after_retention_period(): void
    {
        config()->set('privacy.retention_years', 1);
        $administrator = User::factory()->administrator()->create();
        [, $member] = $this->tenantWithMember('invoice@example.test', [
            'status' => MemberStatus::Archived,
            'left_at' => today()->subYears(2),
            'archived_at' => now()->subYears(2),
        ]);
        Invoice::factory()->create([
            'member_id' => $member->id,
            'issued_at' => today()->subYears(2),
            'payment_status' => InvoicePaymentStatus::Open,
        ]);
        $request = PrivacyErasureRequest::create([
            'member_id' => $member->id,
            'requested_by' => $administrator->id,
            'status' => PrivacyErasureStatus::Pending,
            'requested_at' => now(),
        ]);

        $this->actingAs($administrator)
            ->post(route('privacy-erasure-requests.review', $request))
            ->assertRedirect();

        $this->assertSame(PrivacyErasureStatus::Blocked, $request->fresh()->status);
        $this->assertStringContainsString(
            'Rechnungen',
            implode(' ', $request->fresh()->blockers),
        );
    }

    /**
     * @param  array<string, mixed>  $memberAttributes
     * @return array{User, Member}
     */
    private function tenantWithMember(string $email, array $memberAttributes = []): array
    {
        $user = User::factory()->create([
            'role' => UserRole::Tenant,
            'email' => $email,
            'email_verified_at' => now(),
        ]);
        $member = Member::factory()->create([
            ...$memberAttributes,
            'user_id' => $user->id,
            'email' => $email,
        ]);

        return [$user, $member];
    }
}
