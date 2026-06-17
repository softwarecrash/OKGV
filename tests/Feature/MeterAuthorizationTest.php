<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Member;
use App\Models\Meter;
use App\Models\Parcel;
use App\Models\ParcelTenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeterAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_roles_have_expected_meter_access(): void
    {
        foreach ([UserRole::Board, UserRole::WaterManager] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get(route('meters.create'))->assertOk();
        }

        foreach ([UserRole::Treasurer, UserRole::GardenManager] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get(route('meters.index'))->assertOk();
            $this->actingAs($user)->get(route('meters.create'))->assertForbidden();
        }
    }

    public function test_tenant_only_sees_meters_from_own_parcels(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::Tenant]);
        $member = Member::factory()->create(['user_id' => $tenant->id]);
        $ownParcel = Parcel::factory()->create();
        $otherParcel = Parcel::factory()->create();
        ParcelTenant::factory()->create([
            'parcel_id' => $ownParcel->id,
            'member_id' => $member->id,
        ]);
        $ownMeter = Meter::factory()->create([
            'parcel_id' => $ownParcel->id,
            'meter_number' => 'OWN-METER',
        ]);
        $otherMeter = Meter::factory()->create([
            'parcel_id' => $otherParcel->id,
            'meter_number' => 'FOREIGN-METER',
        ]);

        $this->actingAs($tenant)
            ->get(route('meters.index'))
            ->assertOk()
            ->assertSee($ownMeter->meter_number)
            ->assertDontSee($otherMeter->meter_number);

        $this->actingAs($tenant)->get(route('meters.show', $ownMeter))->assertOk();
        $this->actingAs($tenant)->get(route('meters.show', $otherMeter))->assertForbidden();
        $this->actingAs($tenant)->get(route('meter-readings.create'))->assertForbidden();
    }
}
