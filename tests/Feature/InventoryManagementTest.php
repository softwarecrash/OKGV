<?php

namespace Tests\Feature;

use App\Enums\InventoryItemStatus;
use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Models\InventoryItem;
use App\Models\InventoryLoan;
use App\Models\Member;
use App\Models\User;
use App\Services\ActionIndicatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_and_update_an_inventory_item(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)
            ->post(route('inventory-items.store'), [
                'inventory_number' => ' INV-001 ',
                'name' => ' Rasenmäher ',
                'category' => ' Gerät ',
                'description' => ' Benzinmäher ',
                'status' => InventoryItemStatus::Available->value,
                'location' => ' Geräteschuppen ',
                'purchased_at' => '2025-04-15',
                'purchase_price' => '499,95',
                'serial_number' => ' SN-123 ',
                'notes' => '',
            ])
            ->assertRedirect();

        $item = InventoryItem::query()->firstOrFail();
        $this->assertSame('INV-001', $item->inventory_number);
        $this->assertSame('Gerät', $item->category);
        $this->assertSame('499.95', $item->purchase_price);
        $this->assertNull($item->notes);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'inventory.item_created',
            'subject_id' => $item->id,
        ]);

        $this->actingAs($administrator)
            ->put(route('inventory-items.update', $item), [
                'inventory_number' => 'INV-001',
                'name' => 'Rasenmäher',
                'category' => 'Gerät',
                'description' => 'Benzinmäher',
                'status' => InventoryItemStatus::Maintenance->value,
                'location' => 'Werkstatt',
                'purchased_at' => '2025-04-15',
                'purchase_price' => '499.95',
                'serial_number' => 'SN-123',
                'notes' => 'Messer schärfen',
            ])
            ->assertRedirect(route('inventory-items.show', $item));

        $this->assertSame(
            InventoryItemStatus::Maintenance,
            $item->fresh()->status,
        );
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'inventory.item_updated',
            'subject_id' => $item->id,
        ]);
    }

    public function test_item_can_be_issued_to_a_member_and_returned_with_history(): void
    {
        $administrator = User::factory()->administrator()->create();
        $member = Member::factory()->create([
            'first_name' => 'Erika',
            'last_name' => 'Mustermann',
        ]);
        $item = $this->createItem();

        $this->actingAs($administrator)
            ->post(route('inventory-items.loans.store', $item), [
                'member_id' => $member->id,
                'borrower_name' => 'Manipulierter Name',
                'issued_at' => '2026-06-01',
                'due_at' => '2026-06-10',
                'condition_on_issue' => 'Vollständig und funktionsfähig',
                'notes' => 'Mit Schlüssel',
            ])
            ->assertRedirect(route('inventory-items.show', $item));

        $loan = InventoryLoan::query()->firstOrFail();
        $this->assertSame(InventoryItemStatus::Issued, $item->fresh()->status);
        $this->assertSame('Erika Mustermann', $loan->borrower_name);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'inventory.item_issued',
            'subject_id' => $item->id,
        ]);

        $this->actingAs($administrator)
            ->put(route('inventory-items.loans.return.update', [$item, $loan]), [
                'returned_at' => '2026-06-11',
                'return_status' => InventoryItemStatus::Maintenance->value,
                'condition_on_return' => 'Reifen muss geprüft werden',
            ])
            ->assertRedirect(route('inventory-items.show', $item));

        $this->assertSame(InventoryItemStatus::Maintenance, $item->fresh()->status);
        $this->assertSame('2026-06-11', $loan->fresh()->returned_at->toDateString());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'inventory.item_returned',
            'subject_id' => $item->id,
        ]);

        $this->actingAs($administrator)
            ->get(route('inventory-items.show', $item))
            ->assertOk()
            ->assertSee('Erika Mustermann')
            ->assertSee('Vollständig und funktionsfähig')
            ->assertSee('Reifen muss geprüft werden');
    }

    public function test_an_item_cannot_be_issued_twice_or_returned_twice(): void
    {
        $administrator = User::factory()->administrator()->create();
        $item = $this->createItem();

        $payload = [
            'borrower_name' => 'Max Beispiel',
            'issued_at' => '2026-06-01',
            'due_at' => null,
        ];

        $this->actingAs($administrator)
            ->post(route('inventory-items.loans.store', $item), $payload)
            ->assertRedirect();
        $loan = InventoryLoan::query()->firstOrFail();

        $this->actingAs($administrator)
            ->post(route('inventory-items.loans.store', $item), $payload)
            ->assertSessionHasErrors('inventory_item');
        $this->assertDatabaseCount('inventory_loans', 1);

        $returnPayload = [
            'returned_at' => '2026-06-02',
            'return_status' => InventoryItemStatus::Available->value,
        ];
        $this->actingAs($administrator)
            ->put(route('inventory-items.loans.return.update', [$item, $loan]), $returnPayload)
            ->assertRedirect();
        $this->actingAs($administrator)
            ->put(route('inventory-items.loans.return.update', [$item, $loan]), $returnPayload)
            ->assertSessionHasErrors('inventory_item');
    }

    public function test_inventory_requires_its_own_permission(): void
    {
        $item = $this->createItem();
        $tenant = User::factory()->create(['role' => UserRole::Tenant]);
        $boardWithoutPermission = User::factory()->create([
            'role' => UserRole::Board,
            'permissions' => [],
        ]);
        $boardWithPermission = User::factory()->create([
            'role' => UserRole::Board,
            'permissions' => [UserPermission::ManageInventory->value],
        ]);

        foreach ([$tenant, $boardWithoutPermission] as $user) {
            $this->actingAs($user)
                ->get(route('inventory-items.index'))
                ->assertForbidden();
            $this->actingAs($user)
                ->get(route('inventory-items.show', $item))
                ->assertForbidden();
        }

        $this->actingAs($boardWithPermission)
            ->get(route('inventory-items.index'))
            ->assertOk()
            ->assertSee($item->name);
    }

    public function test_search_filters_and_overdue_indicator_are_available(): void
    {
        $authorized = User::factory()->create([
            'role' => UserRole::Board,
            'permissions' => [UserPermission::ManageInventory->value],
        ]);
        $unauthorized = User::factory()->create([
            'role' => UserRole::Board,
            'permissions' => [],
        ]);
        $item = $this->createItem([
            'name' => 'Vereinsanhänger',
            'category' => 'Anhänger',
            'serial_number' => 'TRAILER-42',
            'status' => InventoryItemStatus::Issued,
        ]);
        InventoryLoan::query()->create([
            'inventory_item_id' => $item->id,
            'borrower_name' => 'Testperson',
            'issued_at' => today()->subDays(10),
            'due_at' => today()->subDay(),
        ]);
        $this->createItem([
            'inventory_number' => 'INV-002',
            'name' => 'Schlüsselkasten',
            'category' => 'Schlüssel',
        ]);

        $this->actingAs($authorized)
            ->get(route('inventory-items.index', [
                'q' => 'TRAILER-42',
                'category' => 'Anhänger',
            ]))
            ->assertOk()
            ->assertSee('Vereinsanhänger')
            ->assertDontSee('Schlüsselkasten')
            ->assertSee('1 Ausgabe ist überfällig');

        $this->assertSame(
            1,
            app(ActionIndicatorService::class)->forUser($authorized)['inventory'],
        );
        $this->assertSame(
            0,
            app(ActionIndicatorService::class)->forUser($unauthorized)['inventory'],
        );
    }

    public function test_inventory_records_have_no_delete_routes_and_cannot_be_deleted(): void
    {
        $this->assertFalse(app('router')->has('inventory-items.destroy'));

        $item = $this->createItem();
        $this->expectException(LogicException::class);
        $item->delete();
    }

    public function test_inventory_loans_cannot_be_deleted(): void
    {
        $loan = InventoryLoan::query()->create([
            'inventory_item_id' => $this->createItem()->id,
            'borrower_name' => 'Testperson',
            'issued_at' => today(),
        ]);

        $this->expectException(LogicException::class);
        $loan->delete();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createItem(array $attributes = []): InventoryItem
    {
        return InventoryItem::query()->create([
            'inventory_number' => 'INV-001',
            'name' => 'Testgegenstand',
            'status' => InventoryItemStatus::Available,
            ...$attributes,
        ]);
    }
}
