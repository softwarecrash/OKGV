<?php

namespace Tests\Feature;

use App\Enums\FeatureModule;
use App\Enums\InventoryItemStatus;
use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Models\BillingPeriod;
use App\Models\InventoryItem;
use App\Models\Member;
use App\Models\Parcel;
use App\Models\ParcelTenant;
use App\Models\PermissionProfile;
use App\Models\User;
use App\Models\WorkHour;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class ModuleConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabled_modules_reject_direct_routes_even_for_administrators(): void
    {
        $administrator = User::factory()->administrator()->create();
        $routes = [
            FeatureModule::TenantPortal->value => 'tenant-portal.index',
            FeatureModule::Meters->value => 'meters.index',
            FeatureModule::Billing->value => 'billing-periods.index',
            FeatureModule::WorkHours->value => 'work-hours.index',
            FeatureModule::WorkEvents->value => 'work-events.index',
            FeatureModule::Sepa->value => 'sepa-mandates.index',
            FeatureModule::Dunning->value => 'dunning-notices.index',
            FeatureModule::Documents->value => 'documents.index',
            FeatureModule::Communication->value => 'mail-campaigns.index',
            FeatureModule::WaitingList->value => 'waiting-list-entries.index',
            FeatureModule::Inventory->value => 'inventory-items.index',
            FeatureModule::DataTransfer->value => 'data-transfer.index',
        ];

        foreach ($routes as $module => $routeName) {
            config()->set("modules.{$module}", false);

            $this->actingAs($administrator)
                ->get(route($routeName))
                ->assertNotFound();

            config()->set("modules.{$module}", true);
        }
    }

    public function test_invalid_module_dependencies_are_rejected(): void
    {
        config()->set('modules.billing', false);
        config()->set('modules.work_hours', true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('work_hours requires billing');

        app(ModuleManager::class)->ensureValidConfiguration();
    }

    public function test_disabled_module_disappears_from_navigation_and_rights(): void
    {
        config()->set('modules.inventory', false);
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee(route('inventory-items.index'));

        $this->actingAs($administrator)
            ->get(route('user-permissions.index'))
            ->assertOk()
            ->assertDontSee(UserPermission::ManageInventory->label());
    }

    public function test_disabled_tenant_portal_hides_public_registration_link(): void
    {
        config()->set('modules.tenant_portal', false);

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Zugang mit Parzellennummer beantragen');

        $this->get(route('tenant-registration.create'))
            ->assertNotFound();
    }

    public function test_disabling_and_reenabling_a_module_preserves_its_data(): void
    {
        $administrator = User::factory()->administrator()->create();
        $item = InventoryItem::query()->create([
            'inventory_number' => 'MODULE-001',
            'name' => 'Erhaltener Gegenstand',
            'status' => InventoryItemStatus::Available,
        ]);

        config()->set('modules.inventory', false);
        $this->actingAs($administrator)
            ->get(route('inventory-items.show', $item))
            ->assertNotFound();
        $this->assertDatabaseHas('inventory_items', ['id' => $item->id]);

        config()->set('modules.inventory', true);
        $this->actingAs($administrator)
            ->get(route('inventory-items.show', $item))
            ->assertOk()
            ->assertSee('Erhaltener Gegenstand');
    }

    public function test_disabled_work_hours_do_not_create_accounts_for_new_periods(): void
    {
        config()->set('modules.work_events', false);
        config()->set('modules.work_hours', false);

        $administrator = User::factory()->administrator()->create();
        $parcel = Parcel::factory()->create();
        $member = Member::factory()->create();
        ParcelTenant::factory()->create([
            'parcel_id' => $parcel->id,
            'member_id' => $member->id,
            'starts_at' => '2026-01-01',
            'ends_at' => null,
        ]);

        $this->actingAs($administrator)
            ->post(route('billing-periods.store'), [
                'name' => 'Abrechnung 2026',
                'starts_at' => '2026-01-01',
                'ends_at' => '2026-12-31',
                'due_at' => '2027-02-01',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('billing_periods', 1);
        $this->assertSame(0, WorkHour::query()->count());
    }

    public function test_module_status_is_visible_in_global_configuration(): void
    {
        config()->set('modules.inventory', false);
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)
            ->get(route('application-settings.edit'))
            ->assertOk()
            ->assertSee('Aktive Funktionsmodule')
            ->assertSeeInOrder(['Inventarverwaltung', 'Deaktiviert']);
    }

    public function test_disabled_module_permissions_survive_profile_and_user_updates(): void
    {
        $administrator = User::factory()->administrator()->create();
        $profile = PermissionProfile::query()->create([
            'name' => 'Inventarprofil',
            'permissions' => [UserPermission::ManageInventory->value],
            'is_active' => true,
            'created_by' => $administrator->id,
        ]);
        $board = User::factory()->create([
            'role' => UserRole::Board,
            'permissions' => [UserPermission::ManageInventory->value],
        ]);
        config()->set('modules.inventory', false);

        $this->actingAs($administrator)
            ->put(route('permission-profiles.update', $profile), [
                'name' => 'Inventarprofil',
                'description' => 'Bleibt erhalten',
                'permissions' => [UserPermission::ViewAllMasterData->value],
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->actingAs($administrator)
            ->put(route('user-permissions.update', $board), [
                'role' => UserRole::Board->value,
                'permissions' => [UserPermission::ViewAllMasterData->value],
            ])
            ->assertRedirect();

        $this->assertContains(
            UserPermission::ManageInventory->value,
            $profile->fresh()->permissions,
        );
        $this->assertContains(
            UserPermission::ManageInventory->value,
            $board->fresh()->permissions,
        );
    }

    public function test_meter_dependent_prices_are_rejected_when_meter_module_is_disabled(): void
    {
        config()->set('modules.meters', false);
        $administrator = User::factory()->administrator()->create();
        $period = BillingPeriod::factory()->create([
            'starts_at' => '2026-01-01',
            'ends_at' => '2026-12-31',
            'due_at' => '2027-02-01',
        ]);

        $this->actingAs($administrator)
            ->post(route('billing-periods.billing-rates.store', $period), [
                'code' => 'WATER_PER_M3',
                'name' => 'Wasser',
                'calculation_type' => 'per_m3',
                'scope' => 'parcel',
                'settlement_type' => 'arrears',
                'service_starts_at' => '2026-01-01',
                'service_ends_at' => '2026-12-31',
                'amount' => '2.50',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('calculation_type');

        $this->assertDatabaseCount('billing_rates', 0);
    }

    public function test_cross_module_mail_groups_follow_module_configuration(): void
    {
        config()->set('modules.meters', false);
        config()->set('modules.work_events', false);
        config()->set('modules.work_hours', false);
        config()->set('modules.sepa', false);
        config()->set('modules.dunning', false);
        config()->set('modules.billing', false);
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)
            ->get(route('mail-campaigns.create'))
            ->assertOk()
            ->assertDontSee('Empfänger offener Rechnungen')
            ->assertDontSee('Fehlende Endstände der letzten Abrechnungsperiode');
    }
}
