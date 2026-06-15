<?php

namespace Tests\Feature;

use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Enums\WaitingListStatus;
use App\Models\User;
use App\Models\WaitingListEntry;
use App\Services\ActionIndicatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class WaitingListWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_and_update_an_entry(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)
            ->post(route('waiting-list-entries.store'), [
                'first_name' => '  Anna ',
                'last_name' => ' Beispiel ',
                'email' => ' ANNA@EXAMPLE.ORG ',
                'phone' => ' 03643 123456 ',
                'mobile' => '',
                'applied_at' => '2026-05-12',
                'priority' => 2,
                'status' => WaitingListStatus::Waiting->value,
                'notes' => ' Sucht einen kleinen Garten. ',
            ])
            ->assertRedirect();

        $entry = WaitingListEntry::query()->firstOrFail();
        $this->assertSame('Anna', $entry->first_name);
        $this->assertSame('Beispiel', $entry->last_name);
        $this->assertSame('anna@example.org', $entry->email);
        $this->assertNull($entry->mobile);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'waiting_list.entry_created',
            'subject_id' => $entry->id,
        ]);

        $this->actingAs($administrator)
            ->put(route('waiting-list-entries.update', $entry), [
                'first_name' => 'Anna',
                'last_name' => 'Beispiel',
                'email' => 'anna@example.org',
                'phone' => '03643 123456',
                'mobile' => '0170 1234567',
                'applied_at' => '2026-05-12',
                'priority' => 1,
                'status' => WaitingListStatus::Contacted->value,
                'notes' => 'Telefonisch erreicht.',
            ])
            ->assertRedirect(route('waiting-list-entries.show', $entry));

        $entry = $entry->fresh();
        $this->assertSame(1, $entry->priority);
        $this->assertSame(WaitingListStatus::Contacted, $entry->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'waiting_list.entry_updated',
            'subject_id' => $entry->id,
        ]);
    }

    public function test_list_defaults_to_open_entries_and_sorts_by_priority_then_date(): void
    {
        $administrator = User::factory()->administrator()->create();
        WaitingListEntry::factory()->create([
            'first_name' => 'Später',
            'last_name' => 'Priorität',
            'priority' => 2,
            'applied_at' => '2026-01-01',
            'status' => WaitingListStatus::Waiting,
        ]);
        WaitingListEntry::factory()->create([
            'first_name' => 'Jünger',
            'last_name' => 'Hoch',
            'priority' => 1,
            'applied_at' => '2026-02-01',
            'status' => WaitingListStatus::Offered,
        ]);
        WaitingListEntry::factory()->create([
            'first_name' => 'Älter',
            'last_name' => 'Hoch',
            'priority' => 1,
            'applied_at' => '2026-01-15',
            'status' => WaitingListStatus::Contacted,
        ]);
        WaitingListEntry::factory()->create([
            'first_name' => 'Bereits',
            'last_name' => 'Übernommen',
            'priority' => 1,
            'status' => WaitingListStatus::Accepted,
        ]);

        $this->actingAs($administrator)
            ->get(route('waiting-list-entries.index'))
            ->assertOk()
            ->assertSeeInOrder(['Älter Hoch', 'Jünger Hoch', 'Später Priorität'])
            ->assertDontSee('Bereits Übernommen');

        $this->actingAs($administrator)
            ->get(route('waiting-list-entries.index', [
                'status' => WaitingListStatus::Accepted->value,
            ]))
            ->assertOk()
            ->assertSee('Bereits Übernommen')
            ->assertDontSee('Älter Hoch');
    }

    public function test_waiting_list_requires_its_own_permission(): void
    {
        $entry = WaitingListEntry::factory()->create();
        $tenant = User::factory()->create(['role' => UserRole::Tenant]);
        $boardWithoutPermission = User::factory()->create([
            'role' => UserRole::Board,
            'permissions' => [],
        ]);
        $boardWithPermission = User::factory()->create([
            'role' => UserRole::Board,
            'permissions' => [UserPermission::ManageWaitingList->value],
        ]);

        foreach ([$tenant, $boardWithoutPermission] as $user) {
            $this->actingAs($user)
                ->get(route('waiting-list-entries.index'))
                ->assertForbidden();
            $this->actingAs($user)
                ->get(route('waiting-list-entries.show', $entry))
                ->assertForbidden();
        }

        $this->actingAs($boardWithPermission)
            ->get(route('waiting-list-entries.index'))
            ->assertOk()
            ->assertSee($entry->full_name);
    }

    public function test_priority_and_status_are_validated(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)
            ->post(route('waiting-list-entries.store'), [
                'first_name' => 'Max',
                'last_name' => 'Muster',
                'email' => 'max@example.org',
                'applied_at' => '2026-06-15',
                'priority' => 0,
                'status' => 'unknown',
            ])
            ->assertSessionHasErrors(['priority', 'status']);

        $this->assertDatabaseCount('waiting_list_entries', 0);
    }

    public function test_open_entries_are_indicated_only_for_authorized_users(): void
    {
        WaitingListEntry::factory()->count(2)->create([
            'status' => WaitingListStatus::Waiting,
        ]);
        WaitingListEntry::factory()->create([
            'status' => WaitingListStatus::Accepted,
        ]);
        $authorized = User::factory()->create([
            'role' => UserRole::Board,
            'permissions' => [UserPermission::ManageWaitingList->value],
        ]);
        $unauthorized = User::factory()->create([
            'role' => UserRole::Board,
            'permissions' => [],
        ]);

        $authorizedIndicators = app(ActionIndicatorService::class)->forUser($authorized);
        $this->assertSame(2, $authorizedIndicators['waiting_list']);
        $this->assertSame(2, $authorizedIndicators['members_group']);

        $unauthorizedIndicators = app(ActionIndicatorService::class)->forUser($unauthorized);
        $this->assertSame(0, $unauthorizedIndicators['waiting_list']);
        $this->assertSame(0, $unauthorizedIndicators['members_group']);

        $this->actingAs($authorized)
            ->get(route('waiting-list-entries.index'))
            ->assertOk()
            ->assertSee('2 offene Wartelisteneinträge');
    }

    public function test_waiting_list_entries_have_no_delete_route(): void
    {
        $this->assertFalse(app('router')->has('waiting-list-entries.destroy'));
    }

    public function test_waiting_list_entries_cannot_be_deleted_at_model_level(): void
    {
        $entry = WaitingListEntry::factory()->create();

        $this->expectException(LogicException::class);
        $entry->delete();
    }
}
