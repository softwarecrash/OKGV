<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Enums\UserRole;
use App\Models\Member;
use App\Models\Parcel;
use App\Models\ParcelTenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParcelMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_sees_placed_and_unplaced_parcels(): void
    {
        $administrator = User::factory()->administrator()->create();
        $placed = Parcel::factory()->create([
            'parcel_number' => 'PLAN-01',
            'status' => ParcelStatus::Blocked,
            'map_x' => 100,
            'map_y' => 120,
            'map_width' => 180,
            'map_height' => 160,
        ]);
        $unplaced = Parcel::factory()->create([
            'parcel_number' => 'PLAN-02',
            'status' => ParcelStatus::Reserved,
        ]);

        $this->actingAs($administrator)
            ->get(route('parcel-map.index'))
            ->assertOk()
            ->assertSee('Lageplan')
            ->assertSee('PLAN-01')
            ->assertSee('PLAN-02')
            ->assertSee('#C62828')
            ->assertSee(route('parcels.show', $placed), false)
            ->assertSee(route('parcels.edit', $unplaced), false);
    }

    public function test_tenant_only_sees_currently_assigned_own_parcels(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::Tenant]);
        $member = Member::factory()->create(['user_id' => $tenant->id]);
        $ownParcel = Parcel::factory()->create([
            'parcel_number' => 'EIGEN-01',
            'map_x' => 10,
            'map_y' => 10,
            'map_width' => 120,
            'map_height' => 100,
        ]);
        $foreignParcel = Parcel::factory()->create([
            'parcel_number' => 'FREMD-99',
            'map_x' => 200,
            'map_y' => 10,
            'map_width' => 120,
            'map_height' => 100,
        ]);
        ParcelTenant::factory()->create([
            'parcel_id' => $ownParcel->id,
            'member_id' => $member->id,
            'starts_at' => now()->subYear(),
            'ends_at' => null,
        ]);

        $this->actingAs($tenant)
            ->get(route('parcel-map.index'))
            ->assertOk()
            ->assertSee('EIGEN-01')
            ->assertDontSee('FREMD-99')
            ->assertDontSee('Platzieren');

        $this->assertDatabaseHas('parcels', ['id' => $foreignParcel->id]);
    }

    public function test_map_coordinates_require_a_complete_in_bounds_rectangle(): void
    {
        $administrator = User::factory()->administrator()->create();

        $baseData = [
            'parcel_number' => 'PLAN-03',
            'area_sqm' => '320.00',
            'status' => ParcelStatus::Free->value,
        ];

        $this->actingAs($administrator)
            ->post(route('parcels.store'), [
                ...$baseData,
                'map_x' => 100,
            ])
            ->assertSessionHasErrors('map_x');

        $this->actingAs($administrator)
            ->post(route('parcels.store'), [
                ...$baseData,
                'map_x' => 1100,
                'map_y' => 700,
                'map_width' => 200,
                'map_height' => 120,
            ])
            ->assertSessionHasErrors(['map_width', 'map_height']);

        $this->assertDatabaseMissing('parcels', ['parcel_number' => 'PLAN-03']);
    }

    public function test_map_coordinates_are_saved_and_audited_with_parcel(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)
            ->post(route('parcels.store'), [
                'parcel_number' => 'PLAN-04',
                'area_sqm' => '280.00',
                'status' => ParcelStatus::Assigned->value,
                'map_x' => 40,
                'map_y' => 60,
                'map_width' => 160,
                'map_height' => 180,
            ])
            ->assertRedirect();

        $parcel = Parcel::query()->where('parcel_number', 'PLAN-04')->firstOrFail();
        $this->assertTrue($parcel->isPlacedOnMap());
        $this->assertSame(40, $parcel->map_x);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'parcel.created',
            'subject_id' => $parcel->id,
        ]);
    }
}
