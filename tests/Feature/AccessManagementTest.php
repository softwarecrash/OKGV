<?php

namespace Tests\Feature;

use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Models\ApplicationSetting;
use App\Models\PermissionProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_promote_tenant_to_board_with_permission_profile(): void
    {
        $administrator = User::factory()->administrator()->create();
        $tenant = User::factory()->create(['role' => UserRole::Tenant]);
        $profile = PermissionProfile::factory()->create([
            'permissions' => [
                UserPermission::ViewAllMasterData->value,
                UserPermission::ReviewTenantRegistrations->value,
            ],
        ]);

        $this->actingAs($administrator)
            ->put(route('user-permissions.update', $tenant), [
                'role' => UserRole::Board->value,
                'permission_profile_id' => $profile->id,
            ])
            ->assertRedirect();

        $tenant->refresh();
        $this->assertSame(UserRole::Board, $tenant->role);
        $this->assertTrue($tenant->canViewAllMasterData());
        $this->assertTrue($tenant->canReviewTenantRegistrations());
        $this->assertFalse($tenant->canManageBilling());
        $this->assertFalse($tenant->canManageSepa());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.access.updated',
            'subject_id' => $tenant->id,
        ]);
    }

    public function test_profile_changes_do_not_retroactively_change_assigned_user(): void
    {
        $administrator = User::factory()->administrator()->create();
        $board = User::factory()->create(['role' => UserRole::Tenant]);
        $profile = PermissionProfile::factory()->create([
            'permissions' => [UserPermission::ViewAllMasterData->value],
        ]);

        $this->actingAs($administrator)
            ->put(route('user-permissions.update', $board), [
                'role' => UserRole::Board->value,
                'permission_profile_id' => $profile->id,
            ])
            ->assertRedirect();

        $profile->update([
            'permissions' => [UserPermission::ManageSepa->value],
        ]);

        $board->refresh();
        $this->assertTrue($board->canViewAllMasterData());
        $this->assertFalse($board->canManageSepa());
    }

    public function test_non_administrator_cannot_change_roles_or_permission_profiles(): void
    {
        $board = User::factory()->create(['role' => UserRole::Board]);
        $tenant = User::factory()->create();

        $this->actingAs($board)
            ->put(route('user-permissions.update', $tenant), [
                'role' => UserRole::Board->value,
                'permissions' => [UserPermission::ManageSepa->value],
            ])
            ->assertForbidden();

        $this->actingAs($board)
            ->get(route('permission-profiles.index'))
            ->assertForbidden();
    }

    public function test_global_system_name_is_used_in_the_interface(): void
    {
        $administrator = User::factory()->administrator()->create();
        $settings = ApplicationSetting::current();
        $profile = PermissionProfile::query()->firstOrFail();

        $this->actingAs($administrator)
            ->put(route('application-settings.update'), [
                'system_name' => 'KGV Sonnental',
                'association_name' => 'Kleingartenverein Sonnental e. V.',
                'street' => 'Gartenweg 1',
                'zip' => '99423',
                'city' => 'Weimar',
                'contact_name' => 'Der Vorstand',
                'phone' => '03643 123456',
                'email' => 'vorstand@sonnental.test',
                'website' => null,
                'remove_logo' => '0',
                'bank_account_holder' => null,
                'bank_name' => null,
                'bank_iban' => null,
                'bank_bic' => null,
                'clear_bank_details' => '0',
                'default_payment_term_days' => '14',
                'document_footer' => null,
                'email_signature' => null,
                'default_board_permission_profile_id' => $profile->id,
                'default_work_hours_required' => '8.00',
                'default_work_hour_penalty_rate' => '15.00',
            ])
            ->assertRedirect();

        $this->actingAs($administrator)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('KGV Sonnental')
            ->assertDontSee('Willkommen bei OKGV');

        $this->assertSame('KGV Sonnental', $settings->fresh()->system_name);
    }

    public function test_unverified_user_is_redirected_to_verification_notice(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('home'))
            ->assertRedirect(route('verification.notice'));
    }
}
