<?php

namespace Tests\Feature;

use App\Enums\MemberStatus;
use App\Models\ApplicationSetting;
use App\Models\BillingPeriod;
use App\Models\Member;
use App\Models\Parcel;
use App\Models\ParcelTenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_search_update_and_archive_member(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)->post(route('members.store'), [
            'member_number' => 'M-100',
            'first_name' => 'Erika',
            'last_name' => 'Mustermann',
            'street' => 'Gartenweg 1',
            'zip' => '12345',
            'city' => 'Musterstadt',
            'email' => 'erika@example.test',
            'joined_at' => '2020-01-01',
            'status' => MemberStatus::Active->value,
        ])->assertRedirect();

        $member = Member::query()->where('member_number', 'M-100')->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member.created',
            'subject_id' => $member->id,
        ]);

        $this->actingAs($administrator)
            ->get(route('members.index', ['q' => 'Mustermann']))
            ->assertSee('Erika Mustermann');

        $this->actingAs($administrator)->put(route('members.update', $member), [
            'member_number' => 'M-100',
            'first_name' => 'Erika',
            'last_name' => 'Musterfrau',
            'street' => 'Gartenweg 1',
            'zip' => '12345',
            'city' => 'Musterstadt',
            'email' => 'erika@example.test',
            'joined_at' => '2020-01-01',
            'status' => MemberStatus::Active->value,
        ])->assertRedirect(route('members.show', $member));

        $this->actingAs($administrator)
            ->patch(route('members.archive', $member))
            ->assertRedirect(route('members.index'));

        $member->refresh();
        $this->assertSame(MemberStatus::Archived, $member->status);
        $this->assertNotNull($member->archived_at);
    }

    public function test_administrator_can_create_and_update_parcel(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)->post(route('parcels.store'), [
            'parcel_number' => 'P-42',
            'area_sqm' => '321.50',
            'status' => 'free',
            'location_description' => 'Am Vereinshaus',
        ])->assertRedirect();

        $parcel = Parcel::query()->where('parcel_number', 'P-42')->firstOrFail();

        $this->actingAs($administrator)->put(route('parcels.update', $parcel), [
            'parcel_number' => 'P-42',
            'area_sqm' => '322.00',
            'status' => 'reserved',
            'location_description' => 'Am Vereinshaus',
        ])->assertRedirect(route('parcels.show', $parcel));

        $this->assertDatabaseHas('parcels', [
            'id' => $parcel->id,
            'status' => 'reserved',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'parcel.updated',
            'subject_id' => $parcel->id,
        ]);
    }

    public function test_overlapping_primary_tenancy_is_rejected(): void
    {
        $administrator = User::factory()->administrator()->create();
        $parcel = Parcel::factory()->create();
        $firstMember = Member::factory()->create();
        $secondMember = Member::factory()->create();

        ParcelTenant::factory()->create([
            'parcel_id' => $parcel->id,
            'member_id' => $firstMember->id,
            'starts_at' => '2024-01-01',
            'ends_at' => null,
            'is_primary' => true,
        ]);

        $this->actingAs($administrator)->post(route('parcel-tenants.store'), [
            'parcel_id' => $parcel->id,
            'member_id' => $secondMember->id,
            'starts_at' => '2025-01-01',
            'is_primary' => true,
        ])->assertSessionHasErrors('is_primary');

        $this->assertDatabaseCount('parcel_tenants', 1);
    }

    public function test_tenancy_history_can_be_closed_but_not_deleted(): void
    {
        $administrator = User::factory()->administrator()->create();
        $tenancy = ParcelTenant::factory()->create([
            'starts_at' => '2020-01-01',
            'ends_at' => null,
        ]);

        $this->actingAs($administrator)->put(route('parcel-tenants.update', $tenancy), [
            'parcel_id' => $tenancy->parcel_id,
            'member_id' => $tenancy->member_id,
            'starts_at' => '2020-01-01',
            'ends_at' => '2025-12-31',
            'is_primary' => true,
        ])->assertRedirect(route('parcels.show', $tenancy->parcel_id));

        $this->assertDatabaseHas('parcel_tenants', [
            'id' => $tenancy->id,
            'ends_at' => '2025-12-31 00:00:00',
        ]);
        $this->delete('/parcel-tenants/'.$tenancy->id)->assertMethodNotAllowed();
    }

    public function test_new_tenancy_automatically_creates_matching_work_hour_account(): void
    {
        $administrator = User::factory()->administrator()->create();
        $period = BillingPeriod::factory()->create([
            'starts_at' => '2026-01-01',
            'ends_at' => '2026-12-31',
        ]);
        $parcel = Parcel::factory()->create();
        $member = Member::factory()->create();
        ApplicationSetting::current()->update([
            'default_work_hours_required' => '8.00',
            'default_work_hour_penalty_rate' => '25.00',
        ]);

        $this->actingAs($administrator)->post(route('parcel-tenants.store'), [
            'parcel_id' => $parcel->id,
            'member_id' => $member->id,
            'starts_at' => '2026-01-01',
            'is_primary' => true,
        ])->assertRedirect(route('parcels.show', $parcel));

        $this->assertDatabaseHas('work_hours', [
            'billing_period_id' => $period->id,
            'parcel_id' => $parcel->id,
            'hours_required' => 8,
            'penalty_rate' => 25,
        ]);
        $this->actingAs($administrator)
            ->get(route('parcels.show', $parcel))
            ->assertOk()
            ->assertSee($period->name)
            ->assertDontSee('Konto anlegen');
    }
}
