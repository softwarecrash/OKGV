<?php

namespace Tests\Feature;

use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Models\ApplicationSetting;
use App\Models\Member;
use App\Models\PermissionProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_board_can_promote_and_demote_tenant_without_assigning_sensitive_rights(): void
    {
        $board = User::factory()->create(['role' => UserRole::Board]);
        $tenant = User::factory()->create();

        $this->actingAs($board)
            ->put(route('user-permissions.update', $tenant), [
                'role' => UserRole::Board->value,
                'permissions' => [UserPermission::ManageSepa->value],
            ])
            ->assertRedirect();

        $tenant->refresh();
        $this->assertSame(UserRole::Board, $tenant->role);
        $this->assertFalse($tenant->is_system_admin);
        $this->assertFalse($tenant->canManageSepa());

        $this->actingAs($board)
            ->put(route('user-permissions.update', $tenant), [
                'role' => UserRole::Tenant->value,
            ])
            ->assertRedirect();

        $this->assertSame(UserRole::Tenant, $tenant->fresh()->role);

        $this->actingAs($board)
            ->get(route('permission-profiles.index'))
            ->assertForbidden();
    }

    public function test_system_admin_can_manage_admin_flag_without_getting_board_data_access(): void
    {
        $administrator = User::factory()->administrator()->create([
            'role' => UserRole::Tenant,
            'is_system_admin' => true,
        ]);
        $member = Member::factory()->create(['user_id' => $administrator->id]);
        $otherUser = User::factory()->create(['role' => UserRole::Tenant]);

        $this->assertTrue($administrator->isAdministrator());
        $this->assertTrue($administrator->hasTenantAccess());
        $this->assertFalse($administrator->canManageBilling());
        $this->assertFalse($administrator->canManageSepa());
        $this->assertFalse($administrator->canViewAllMasterData());

        $this->actingAs($administrator)
            ->get(route('application-settings.edit'))
            ->assertOk();
        $this->actingAs($administrator)
            ->get(route('tenant-portal.index'))
            ->assertOk()
            ->assertSee($member->last_name);
        $this->actingAs($administrator)
            ->get(route('billing-periods.index'))
            ->assertForbidden();

        $this->actingAs($administrator)
            ->put(route('user-permissions.update', $otherUser), [
                'role' => UserRole::Tenant->value,
                'is_system_admin' => '1',
            ])
            ->assertRedirect();

        $otherUser->refresh();
        $this->assertSame(UserRole::Tenant, $otherUser->role);
        $this->assertTrue($otherUser->is_system_admin);
    }

    public function test_last_system_admin_cannot_be_removed(): void
    {
        $administrator = User::factory()->administrator()->create([
            'role' => UserRole::Tenant,
            'is_system_admin' => true,
        ]);

        $this->actingAs($administrator)
            ->put(route('user-permissions.update', $administrator), [
                'role' => UserRole::Tenant->value,
                'is_system_admin' => '0',
            ])
            ->assertForbidden();

        $otherAdmin = User::factory()->administrator()->create([
            'role' => UserRole::Tenant,
            'is_system_admin' => true,
        ]);

        $this->actingAs($administrator)
            ->put(route('user-permissions.update', $otherAdmin), [
                'role' => UserRole::Tenant->value,
                'is_system_admin' => '0',
            ])
            ->assertRedirect();

        $this->assertFalse($otherAdmin->fresh()->is_system_admin);
    }

    public function test_board_cannot_change_system_admin_accounts(): void
    {
        $board = User::factory()->create(['role' => UserRole::Board]);
        $administrator = User::factory()->administrator()->create([
            'role' => UserRole::Tenant,
            'is_system_admin' => true,
        ]);

        $this->actingAs($board)
            ->put(route('user-permissions.update', $administrator), [
                'role' => UserRole::Board->value,
            ])
            ->assertForbidden();
    }

    public function test_authenticated_user_can_change_own_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('AltesPasswort123'),
        ]);

        $this->actingAs($user)
            ->put(route('account.password.update'), [
                'current_password' => 'AltesPasswort123',
                'password' => 'NeuesPasswort123',
                'password_confirmation' => 'NeuesPasswort123',
            ])
            ->assertRedirect(route('account.password.edit'));

        $this->assertTrue(Hash::check('NeuesPasswort123', $user->fresh()->password));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.password.updated',
            'user_id' => $user->id,
        ]);
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
