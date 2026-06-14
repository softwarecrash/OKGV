<?php

namespace Tests\Feature;

use App\Enums\BillingPeriodStatus;
use App\Enums\BillingRateScope;
use App\Enums\BillingRateType;
use App\Enums\InvoiceStatus;
use App\Enums\MeterStatus;
use App\Enums\MeterType;
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
use Illuminate\Validation\ValidationException;
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

    public function test_calculation_rejects_tenant_change_within_period(): void
    {
        $administrator = User::factory()->administrator()->create();
        $period = BillingPeriod::factory()->create([
            'starts_at' => '2025-01-01',
            'ends_at' => '2025-12-31',
            'due_at' => '2026-02-01',
        ]);
        $parcel = Parcel::factory()->create();
        ParcelTenant::factory()->create([
            'parcel_id' => $parcel->id,
            'member_id' => Member::factory(),
            'starts_at' => '2020-01-01',
            'ends_at' => '2025-06-30',
        ]);
        ParcelTenant::factory()->create([
            'parcel_id' => $parcel->id,
            'member_id' => Member::factory(),
            'starts_at' => '2025-07-01',
            'ends_at' => null,
        ]);

        $this->expectException(ValidationException::class);
        app(BillingCalculator::class)->calculate($period, $administrator);
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
        $member = Member::factory()->create();
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
