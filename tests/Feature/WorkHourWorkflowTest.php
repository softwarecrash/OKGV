<?php

namespace Tests\Feature;

use App\Enums\BillingPeriodStatus;
use App\Enums\BillingRateScope;
use App\Enums\BillingRateType;
use App\Models\BillingPeriod;
use App\Models\BillingRate;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Parcel;
use App\Models\ParcelTenant;
use App\Models\User;
use App\Models\WorkHour;
use App\Services\ActionIndicatorService;
use App\Services\BillingCalculator;
use App\Services\BillingPeriodManager;
use App\Services\WorkHourManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class WorkHourWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_work_hours_are_calculated_server_side_and_audited(): void
    {
        $administrator = User::factory()->administrator()->create();
        $period = BillingPeriod::factory()->create();
        $member = Member::factory()->create();

        $this->actingAs($administrator)
            ->post(route('billing-periods.work-hours.store', $period), [
                'member_id' => $member->id,
                'hours_required' => '12.50',
                'hours_done' => '7.25',
                'penalty_rate' => '15.00',
                'notes' => 'Zwei Termine anerkannt.',
            ])
            ->assertRedirect(route('billing-periods.show', $period));

        $workHour = WorkHour::query()->firstOrFail();
        $this->assertSame('5.25', $workHour->hours_missing);
        $this->assertSame('78.75', $workHour->penalty_amount);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'work_hours.created',
            'subject_id' => $workHour->id,
        ]);

        $this->actingAs($administrator)
            ->put(route('work-hours.update', $workHour), [
                'member_id' => $member->id,
                'hours_required' => '12.50',
                'hours_done' => '14.00',
                'penalty_rate' => '15.00',
            ])
            ->assertRedirect(route('billing-periods.show', $period));

        $this->assertSame('0.00', $workHour->fresh()->hours_missing);
        $this->assertSame('0.00', $workHour->fresh()->penalty_amount);
    }

    public function test_changing_work_hours_discards_a_calculated_intermediate_result(): void
    {
        [$administrator, $period, $member] = $this->billingScenario();
        $workHour = WorkHour::factory()->create([
            'billing_period_id' => $period->id,
            'member_id' => $member->id,
            'hours_required' => '10.00',
            'hours_done' => '5.00',
            'hours_missing' => '5.00',
            'penalty_rate' => '20.00',
            'penalty_amount' => '100.00',
        ]);

        app(BillingCalculator::class)->calculate($period, $administrator);
        $this->assertDatabaseCount('invoices', 1);

        $this->actingAs($administrator)
            ->put(route('work-hours.update', $workHour), [
                'member_id' => $member->id,
                'hours_required' => '10.00',
                'hours_done' => '6.00',
                'penalty_rate' => '20.00',
            ])
            ->assertRedirect(route('billing-periods.show', $period));

        $this->assertSame(BillingPeriodStatus::Draft, $period->fresh()->status);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'billing.period.calculation_discarded',
            'subject_id' => $period->id,
        ]);
    }

    public function test_approved_work_hours_cannot_be_changed(): void
    {
        [$administrator, $period, $member] = $this->billingScenario();
        $workHour = WorkHour::factory()->create([
            'billing_period_id' => $period->id,
            'member_id' => $member->id,
        ]);
        app(BillingCalculator::class)->calculate($period, $administrator);
        app(BillingPeriodManager::class)->approve($period->fresh(), $administrator);

        $this->expectException(ValidationException::class);
        app(WorkHourManager::class)->save(
            $period->fresh(),
            [
                'member_id' => $member->id,
                'hours_required' => '10.00',
                'hours_done' => '10.00',
                'penalty_rate' => '20.00',
            ],
            $administrator,
            $workHour,
        );
    }

    public function test_approved_work_hours_are_immutable_at_model_level(): void
    {
        [$administrator, $period, $member] = $this->billingScenario();
        $workHour = WorkHour::factory()->create([
            'billing_period_id' => $period->id,
            'member_id' => $member->id,
        ]);
        app(BillingCalculator::class)->calculate($period, $administrator);
        app(BillingPeriodManager::class)->approve($period->fresh(), $administrator);

        $this->expectException(LogicException::class);
        $workHour->update(['hours_done' => '10.00']);
    }

    public function test_each_contract_party_penalty_is_snapshotted_on_shared_invoice(): void
    {
        [$administrator, $period, $primaryMember, $parcel] = $this->billingScenario();
        $coTenant = Member::factory()->create([
            'first_name' => 'Erika',
            'last_name' => 'Mitpächterin',
        ]);
        ParcelTenant::factory()->create([
            'parcel_id' => $parcel->id,
            'member_id' => $coTenant->id,
            'starts_at' => '2020-01-01',
            'ends_at' => null,
            'is_primary' => false,
        ]);
        WorkHour::factory()->create([
            'billing_period_id' => $period->id,
            'member_id' => $primaryMember->id,
            'hours_required' => '10.00',
            'hours_done' => '8.00',
            'hours_missing' => '2.00',
            'penalty_rate' => '15.00',
            'penalty_amount' => '30.00',
        ]);
        WorkHour::factory()->create([
            'billing_period_id' => $period->id,
            'member_id' => $coTenant->id,
            'hours_required' => '10.00',
            'hours_done' => '5.00',
            'hours_missing' => '5.00',
            'penalty_rate' => '15.00',
            'penalty_amount' => '75.00',
        ]);

        app(BillingCalculator::class)->calculate($period, $administrator);

        $invoice = Invoice::query()->with('items')->firstOrFail();
        $penalties = $invoice->items->where('code', 'WORK_HOURS_PENALTY')->values();
        $this->assertCount(2, $penalties);
        $this->assertEquals(105.00, $penalties->sum('total_amount'));
        $this->assertSame('106.00', $invoice->total_amount);
        $this->assertSame(
            [
                "Fehlende Arbeitsstunden - {$primaryMember->full_name}",
                'Fehlende Arbeitsstunden - Erika Mitpächterin',
            ],
            $penalties->pluck('description')->all(),
        );
    }

    public function test_only_billing_managers_can_access_work_hours_and_open_items_are_indicated(): void
    {
        $administrator = User::factory()->administrator()->create();
        $tenant = User::factory()->create();
        $period = BillingPeriod::factory()->create();
        WorkHour::factory()->create([
            'billing_period_id' => $period->id,
            'hours_missing' => '2.00',
            'penalty_amount' => '20.00',
        ]);

        $this->actingAs($tenant)
            ->get(route('work-hours.index'))
            ->assertForbidden();
        $this->actingAs($administrator)
            ->get(route('work-hours.index'))
            ->assertOk()
            ->assertSee('Arbeitsstunden');

        $indicators = app(ActionIndicatorService::class)->forUser($administrator);
        $this->assertSame(1, $indicators['work_hours']);
        $this->assertSame(1, $indicators['finance_group']);
    }

    /**
     * @return array{User, BillingPeriod, Member, Parcel}
     */
    private function billingScenario(): array
    {
        $administrator = User::factory()->administrator()->create();
        $period = BillingPeriod::factory()->create([
            'name' => 'Abrechnung 2025',
            'starts_at' => '2025-01-01',
            'ends_at' => '2025-12-31',
            'due_at' => '2026-02-01',
        ]);
        $member = Member::factory()->create();
        $parcel = Parcel::factory()->create();
        ParcelTenant::factory()->create([
            'parcel_id' => $parcel->id,
            'member_id' => $member->id,
            'starts_at' => '2020-01-01',
            'ends_at' => null,
            'is_primary' => true,
        ]);
        BillingRate::factory()->create([
            'billing_period_id' => $period->id,
            'code' => 'MEMBER_FEE',
            'name' => 'Mitgliedsbeitrag',
            'calculation_type' => BillingRateType::Fixed,
            'scope' => BillingRateScope::Member,
            'amount' => '1.0000',
        ]);

        return [$administrator, $period, $member, $parcel];
    }
}
