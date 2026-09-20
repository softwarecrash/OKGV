<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Member;
use App\Models\Parcel;
use App\Models\ParcelTenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_board_can_manage_master_data(): void
    {
        $board = User::factory()->create(['role' => UserRole::Board]);

        $this->actingAs($board)->get(route('members.create'))->assertOk();
        $this->actingAs($board)->get(route('parcels.create'))->assertOk();
        $this->actingAs($board)->get(route('parcel-tenants.create'))->assertOk();
    }

    public function test_read_only_roles_cannot_change_master_data(): void
    {
        foreach ([UserRole::Treasurer, UserRole::WaterManager, UserRole::GardenManager] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('members.index'))->assertOk();
            $this->actingAs($user)->get(route('parcels.index'))->assertOk();
            $this->actingAs($user)->get(route('members.create'))->assertForbidden();
            $this->actingAs($user)->get(route('parcels.create'))->assertForbidden();
        }
    }

    public function test_tenant_uses_own_profile_instead_of_member_overview(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::Tenant]);
        $ownMember = Member::factory()->create(['user_id' => $tenant->id]);
        $otherMember = Member::factory()->create(['last_name' => 'Nicht sichtbar']);

        $this->actingAs($tenant)->get(route('members.index'))->assertForbidden();

        $this->actingAs($tenant)->get(route('members.show', $ownMember))->assertOk();
        $this->actingAs($tenant)->get(route('members.show', $otherMember))->assertForbidden();

        $this->actingAs($tenant)
            ->get(route('account.member-profile.edit'))
            ->assertOk()
            ->assertSee('Meine Daten')
            ->assertSee($ownMember->last_name);

        $this->actingAs($tenant)
            ->put(route('account.member-profile.update'), [
                'first_name' => 'Eigene',
                'last_name' => 'Daten',
                'street' => 'Gartenweg 1',
                'zip' => '12345',
                'city' => 'Musterstadt',
                'phone' => '0123',
                'mobile' => '0170',
                'email' => 'kontakt@example.test',
                'status' => 'archived',
            ])
            ->assertRedirect(route('account.member-profile.edit'));

        $this->assertDatabaseHas('members', [
            'id' => $ownMember->id,
            'first_name' => 'Eigene',
            'last_name' => 'Daten',
            'status' => 'active',
        ]);
    }

    public function test_tenant_only_sees_own_parcels_and_tenancy_details(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::Tenant]);
        $ownMember = Member::factory()->create(['user_id' => $tenant->id]);
        $otherMember = Member::factory()->create(['last_name' => 'Mitpächter Geheim']);
        $ownParcel = Parcel::factory()->create();
        $otherParcel = Parcel::factory()->create();

        ParcelTenant::factory()->create([
            'parcel_id' => $ownParcel->id,
            'member_id' => $ownMember->id,
        ]);
        ParcelTenant::factory()->create([
            'parcel_id' => $ownParcel->id,
            'member_id' => $otherMember->id,
            'is_primary' => false,
        ]);

        $this->actingAs($tenant)
            ->get(route('parcels.index'))
            ->assertOk()
            ->assertSee($ownParcel->parcel_number)
            ->assertDontSee($otherParcel->parcel_number);

        $this->actingAs($tenant)
            ->get(route('parcels.show', $ownParcel))
            ->assertOk()
            ->assertSee($ownMember->last_name)
            ->assertDontSee($otherMember->last_name);

        $this->actingAs($tenant)->get(route('parcels.show', $otherParcel))->assertForbidden();
    }
}
