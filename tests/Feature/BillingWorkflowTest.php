<?php

namespace Tests\Feature;

use App\Enums\BillingPeriodStatus;
use App\Enums\BillingRateScope;
use App\Enums\BillingRateType;
use App\Enums\BillingSettlementType;
use App\Enums\InvoiceStatus;
use App\Enums\MeterStatus;
use App\Enums\MeterType;
use App\Models\AuditLog;
use App\Models\BillingPeriod;
use App\Models\BillingRate;
use App\Models\BillingRateAssignment;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Parcel;
use App\Models\ParcelTenant;
use App\Models\User;
use App\Services\BillingCalculator;
use App\Services\BillingPeriodManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class BillingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculation_creates_exact_snapshot_items_and_audit_log(): void
    {
        [$administrator, $period, $member, $parcel] = $this->billingScenario();

        $memberRate = BillingRate::factory()->create([
            'billing_period_id' => $period->id,
            'code' => 'MEMBER_FEE',
            'name' => 'Mitgliedsbeitrag',
            'calculation_type' => BillingRateType::Fixed,
            'scope' => BillingRateScope::Member,
            'amount' => '25.0000',
        ]);
        $leaseRate = BillingRate::factory()->create([
            'billing_period_id' => $period->id,
            'code' => 'LEASE_PER_SQM',
            'name' => 'Pacht',
            'calculation_type' => BillingRateType::PerSquareMeter,
            'scope' => BillingRateScope::Parcel,
            'amount' => '0.5000',
        ]);
        $waterRate = BillingRate::factory()->create([
            'billing_period_id' => $period->id,
            'code' => 'WATER_PER_M3',
            'name' => 'Wasser',
            'calculation_type' => BillingRateType::PerCubicMeter,
            'scope' => BillingRateScope::Parcel,
            'amount' => '2.0000',
        ]);
        $insuranceRate = BillingRate::factory()->create([
            'billing_period_id' => $period->id,
            'code' => 'INSURANCE',
            'name' => 'Versicherung',
            'calculation_type' => BillingRateType::Fixed,
            'scope' => BillingRateScope::Assignment,
            'amount' => '10.0000',
        ]);
        BillingRateAssignment::factory()->create([
            'billing_rate_id' => $insuranceRate->id,
            'member_id' => $member->id,
            'quantity' => '2.0000',
        ]);

        $oldMeter = Meter::factory()->create([
            'parcel_id' => $parcel->id,
            'type' => MeterType::Water,
            'installed_at' => '2025-01-01',
            'removed_at' => '2025-06-30',
            'start_reading' => '10.0000',
            'end_reading' => '40.0000',
            'status' => MeterStatus::Replaced,
        ]);
        $newMeter = Meter::factory()->create([
            'parcel_id' => $parcel->id,
            'type' => MeterType::Water,
            'installed_at' => '2025-06-30',
            'start_reading' => '5.0000',
            'status' => MeterStatus::Active,
        ]);
        MeterReading::factory()->create([
            'meter_id' => $newMeter->id,
            'reading_date' => '2025-12-31',
            'reading_value' => '25.0000',
        ]);

        app(BillingCalculator::class)->calculate($period, $administrator);

        $invoice = Invoice::query()->with('items')->firstOrFail();
        $this->assertSame(BillingPeriodStatus::Calculated, $period->fresh()->status);
        $this->assertSame('195.25', $invoice->total_amount);
        $this->assertCount(4, $invoice->items);
        $this->assertSame('100.5000', $invoice->items->firstWhere('billing_rate_id', $leaseRate->id)->quantity);
        $this->assertSame('50.25', $invoice->items->firstWhere('billing_rate_id', $leaseRate->id)->total_amount);
        $this->assertSame('50.0000', $invoice->items->firstWhere('billing_rate_id', $waterRate->id)->quantity);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'billing.period.calculated',
            'subject_id' => $period->id,
        ]);
        $this->assertDatabaseHas('meters', ['id' => $oldMeter->id]);
        $this->assertSame('25.0000', $invoice->items->firstWhere('billing_rate_id', $memberRate->id)->unit_price);
    }

    public function test_approval_makes_invoice_and_items_immutable(): void
    {
        [$administrator, $period] = $this->billingScenario();
        BillingRate::factory()->create([
            'billing_period_id' => $period->id,
            'code' => 'MEMBER_FEE',
            'calculation_type' => BillingRateType::Fixed,
            'scope' => BillingRateScope::Member,
            'amount' => '25.0000',
        ]);

        app(BillingCalculator::class)->calculate($period, $administrator);
        app(BillingPeriodManager::class)->approve($period->fresh(), $administrator);

        $invoice = Invoice::query()->with('items')->firstOrFail();
        $this->assertSame(InvoiceStatus::Approved, $invoice->status);
        $this->assertSame(BillingPeriodStatus::Approved, $period->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'billing.period.approved',
            'subject_id' => $period->id,
        ]);

        $this->expectException(LogicException::class);
        $invoice->update(['total_amount' => '1.00']);
    }

    public function test_calculated_intermediate_result_can_be_recalculated(): void
    {
        [$administrator, $period] = $this->billingScenario();
        BillingRate::factory()->create([
            'billing_period_id' => $period->id,
            'code' => 'MEMBER_FEE',
            'calculation_type' => BillingRateType::Fixed,
            'scope' => BillingRateScope::Member,
            'amount' => '25.0000',
        ]);

        app(BillingCalculator::class)->calculate($period, $administrator);
        $firstInvoice = Invoice::query()->firstOrFail();

        app(BillingCalculator::class)->calculate($period->fresh(), $administrator);

        $this->assertSame(BillingPeriodStatus::Calculated, $period->fresh()->status);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertModelMissing($firstInvoice);
        $this->assertSame(
            2,
            AuditLog::query()
                ->where('action', 'billing.period.calculated')
                ->where('subject_id', $period->id)
                ->count(),
        );
    }

    public function test_all_contract_parties_are_snapshotted_on_the_shared_invoice(): void
    {
        [$administrator, $period, $primaryMember, $parcel] = $this->billingScenario();
        $coTenantUser = User::factory()->create();
        $coTenant = Member::factory()->create([
            'user_id' => $coTenantUser->id,
            'first_name' => 'Erika',
            'last_name' => 'Mitpächterin',
            'street' => 'Andere Straße 9',
            'zip' => '54321',
            'city' => 'Nebenstadt',
        ]);
        ParcelTenant::factory()->create([
            'parcel_id' => $parcel->id,
            'member_id' => $coTenant->id,
            'starts_at' => '2021-01-01',
            'ends_at' => null,
            'is_primary' => false,
        ]);
        BillingRate::factory()->create([
            'billing_period_id' => $period->id,
            'code' => 'LEASE_PER_SQM',
            'calculation_type' => BillingRateType::PerSquareMeter,
            'scope' => BillingRateScope::Parcel,
            'amount' => '0.5000',
        ]);

        app(BillingCalculator::class)->calculate($period, $administrator);
        app(BillingPeriodManager::class)->approve($period->fresh(), $administrator);

        $invoice = Invoice::query()->with('recipients')->firstOrFail();
        $this->assertCount(2, $invoice->recipients);
        $this->assertSame($primaryMember->id, $invoice->primaryRecipient()?->member_id);
        $this->assertSame(
            [$primaryMember->full_name, 'Erika Mitpächterin'],
            $invoice->recipients->pluck('full_name')->all(),
        );

        $coTenant->update(['first_name' => 'Geänderter Name']);

        $this->actingAs($coTenantUser)
            ->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Erika Mitpächterin')
            ->assertDontSee('Geänderter Name')
            ->assertSee($primaryMember->street)
            ->assertDontSee('Andere Straße 9');
    }

    public function test_tenant_change_is_split_between_both_primary_tenants(): void
    {
        $administrator = User::factory()->administrator()->create();
        $period = BillingPeriod::factory()->create([
            'starts_at' => '2025-01-01',
            'ends_at' => '2025-12-31',
            'due_at' => '2026-02-01',
        ]);
        $parcel = Parcel::factory()->create(['area_sqm' => '100.0000']);
        $firstMember = Member::factory()->create(['joined_at' => '2020-01-01']);
        $secondMember = Member::factory()->create(['joined_at' => '2025-07-01']);
        ParcelTenant::factory()->create([
            'parcel_id' => $parcel->id,
            'member_id' => $firstMember->id,
            'starts_at' => '2020-01-01',
            'ends_at' => '2025-06-30',
        ]);
        ParcelTenant::factory()->create([
            'parcel_id' => $parcel->id,
            'member_id' => $secondMember->id,
            'starts_at' => '2025-07-01',
            'ends_at' => null,
        ]);
        BillingRate::factory()->create([
            'billing_period_id' => $period->id,
            'code' => 'LEASE_PER_SQM',
            'calculation_type' => BillingRateType::PerSquareMeter,
            'scope' => BillingRateScope::Parcel,
            'amount' => '1.0000',
            'prorate' => true,
        ]);

        app(BillingCalculator::class)->calculate($period, $administrator);

        $this->assertDatabaseCount('invoices', 2);
        $firstInvoice = Invoice::query()->where('member_id', $firstMember->id)->with('items')->firstOrFail();
        $secondInvoice = Invoice::query()->where('member_id', $secondMember->id)->with('items')->firstOrFail();
        $this->assertSame('49.59', $firstInvoice->total_amount);
        $this->assertSame('50.41', $secondInvoice->total_amount);
        $this->assertSame('0.49589041', $firstInvoice->items->first()->metadata['proration_factor']);
        $this->assertSame('0.50410958', $secondInvoice->items->first()->metadata['proration_factor']);
    }

    public function test_member_fee_is_prorated_for_midyear_entry_and_exit(): void
    {
        $administrator = User::factory()->administrator()->create();
        $period = BillingPeriod::factory()->create([
            'starts_at' => '2025-01-01',
            'ends_at' => '2025-12-31',
            'due_at' => '2026-02-01',
        ]);
        $member = Member::factory()->create([
            'joined_at' => '2025-07-01',
            'left_at' => '2025-09-30',
        ]);
        BillingRate::factory()->create([
            'billing_period_id' => $period->id,
            'code' => 'MEMBER_FEE',
            'calculation_type' => BillingRateType::Fixed,
            'scope' => BillingRateScope::Member,
            'amount' => '120.0000',
            'prorate' => true,
        ]);

        app(BillingCalculator::class)->calculate($period, $administrator);

        $invoice = Invoice::query()->where('member_id', $member->id)->with('items')->firstOrFail();
        $this->assertSame('30.25', $invoice->total_amount);
        $this->assertSame('0.25205479', $invoice->items->first()->metadata['proration_factor']);
        $this->assertSame([
            [
                'starts_at' => '2025-07-01',
                'ends_at' => '2025-09-30',
            ],
        ], $invoice->items->first()->metadata['usage_periods']);
    }

    public function test_consumption_is_split_at_tenant_change_reading(): void
    {
        $administrator = User::factory()->administrator()->create();
        $period = BillingPeriod::factory()->create([
            'starts_at' => '2025-01-01',
            'ends_at' => '2025-12-31',
            'due_at' => '2026-02-01',
        ]);
        $parcel = Parcel::factory()->create();
        $firstMember = Member::factory()->create(['joined_at' => '2020-01-01']);
        $secondMember = Member::factory()->create(['joined_at' => '2025-07-01']);
        ParcelTenant::factory()->create([
            'parcel_id' => $parcel->id,
            'member_id' => $firstMember->id,
            'starts_at' => '2020-01-01',
            'ends_at' => '2025-06-30',
        ]);
        ParcelTenant::factory()->create([
            'parcel_id' => $parcel->id,
            'member_id' => $secondMember->id,
            'starts_at' => '2025-07-01',
        ]);
        $meter = Meter::factory()->create([
            'parcel_id' => $parcel->id,
            'type' => MeterType::Water,
            'installed_at' => '2025-01-01',
            'start_reading' => '0.0000',
        ]);
        foreach ([
            ['2025-01-01', '0.0000'],
            ['2025-06-30', '30.0000'],
            ['2025-12-31', '80.0000'],
        ] as [$date, $value]) {
            MeterReading::factory()->create([
                'meter_id' => $meter->id,
                'reading_date' => $date,
                'reading_value' => $value,
            ]);
        }
        BillingRate::factory()->create([
            'billing_period_id' => $period->id,
            'code' => 'WATER_PER_M3',
            'calculation_type' => BillingRateType::PerCubicMeter,
            'scope' => BillingRateScope::Parcel,
            'amount' => '2.0000',
        ]);

        app(BillingCalculator::class)->calculate($period, $administrator);

        $this->assertSame(
            '30.0000',
            Invoice::query()->where('member_id', $firstMember->id)->firstOrFail()
                ->items()->firstOrFail()->quantity,
        );
        $this->assertSame(
            '50.0000',
            Invoice::query()->where('member_id', $secondMember->id)->firstOrFail()
                ->items()->firstOrFail()->quantity,
        );
    }

    public function test_one_run_combines_advance_lease_and_arrears_consumption(): void
    {
        [$administrator, $period, $member, $parcel] = $this->billingScenario();
        BillingRate::factory()->create([
            'billing_period_id' => $period->id,
            'code' => 'LEASE_PER_SQM',
            'name' => 'Pacht',
            'calculation_type' => BillingRateType::PerSquareMeter,
            'scope' => BillingRateScope::Parcel,
            'settlement_type' => BillingSettlementType::Advance,
            'service_starts_at' => '2026-01-01',
            'service_ends_at' => '2026-12-31',
            'amount' => '0.5000',
            'prorate' => true,
        ]);
        $waterRate = BillingRate::factory()->create([
            'billing_period_id' => $period->id,
            'code' => 'WATER_PER_M3',
            'name' => 'Wasser',
            'calculation_type' => BillingRateType::PerCubicMeter,
            'scope' => BillingRateScope::Parcel,
            'settlement_type' => BillingSettlementType::Arrears,
            'service_starts_at' => '2025-01-01',
            'service_ends_at' => '2025-12-31',
            'amount' => '2.0000',
        ]);
        $meter = Meter::factory()->create([
            'parcel_id' => $parcel->id,
            'type' => MeterType::Water,
            'installed_at' => '2025-01-01',
            'start_reading' => '10.0000',
        ]);
        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => '2025-12-31',
            'reading_value' => '25.0000',
        ]);

        app(BillingCalculator::class)->calculate($period, $administrator);

        $invoice = Invoice::query()->with('items')->where('member_id', $member->id)->firstOrFail();
        $this->assertCount(2, $invoice->items);
        $this->assertStringContainsString(
            '01.01.2026–31.12.2026',
            $invoice->items->firstWhere('code', 'LEASE_PER_SQM')->description,
        );
        $this->assertSame(
            'arrears',
            $invoice->items->firstWhere('billing_rate_id', $waterRate->id)
                ->metadata['settlement_type'],
        );
    }

    public function test_invoice_pdf_is_generated_and_policy_protected(): void
    {
        $administrator = User::factory()->administrator()->create();
        $invoice = Invoice::factory()->create();

        $response = $this->actingAs($administrator)->get(route('invoices.pdf', $invoice));

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
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
        $member = Member::factory()->create(['joined_at' => '2020-01-01']);
        $parcel = Parcel::factory()->create(['area_sqm' => '100.5000']);
        ParcelTenant::factory()->create([
            'parcel_id' => $parcel->id,
            'member_id' => $member->id,
            'starts_at' => '2020-01-01',
            'ends_at' => null,
            'is_primary' => true,
        ]);

        return [$administrator, $period, $member, $parcel];
    }
}
