<?php

namespace Tests\Feature;

use App\Enums\BillingPeriodStatus;
use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\BillingPeriod;
use App\Models\BillingRate;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_financial_roles_can_manage_billing(): void
    {
        foreach ([UserRole::Administrator, UserRole::Board, UserRole::Treasurer] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('billing-periods.index'))->assertOk();
            $this->actingAs($user)->get(route('billing-periods.create'))->assertOk();
        }
    }

    public function test_non_financial_roles_cannot_access_billing_management(): void
    {
        foreach ([UserRole::WaterManager, UserRole::GardenManager, UserRole::Tenant] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('billing-periods.index'))->assertForbidden();
        }
    }

    public function test_tenant_can_only_view_own_approved_invoice(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::Tenant]);
        $member = Member::factory()->create(['user_id' => $tenant->id]);
        $ownInvoice = Invoice::factory()->create([
            'member_id' => $member->id,
            'status' => InvoiceStatus::Approved,
            'approved_at' => now(),
        ]);
        $draftInvoice = Invoice::factory()->create([
            'member_id' => $member->id,
            'status' => InvoiceStatus::Draft,
        ]);
        $foreignInvoice = Invoice::factory()->create([
            'status' => InvoiceStatus::Approved,
            'approved_at' => now(),
        ]);

        $this->actingAs($tenant)->get(route('invoices.show', $ownInvoice))->assertOk();
        $this->actingAs($tenant)->get(route('invoices.show', $draftInvoice))->assertForbidden();
        $this->actingAs($tenant)->get(route('invoices.show', $foreignInvoice))->assertForbidden();
    }

    public function test_overlapping_billing_period_is_rejected(): void
    {
        $administrator = User::factory()->administrator()->create();
        BillingPeriod::factory()->create([
            'name' => 'Abrechnung 2025',
            'starts_at' => '2025-01-01',
            'ends_at' => '2025-12-31',
            'due_at' => '2026-02-01',
        ]);

        $this->actingAs($administrator)->post(route('billing-periods.store'), [
            'name' => 'Überschneidung',
            'starts_at' => '2025-12-01',
            'ends_at' => '2026-11-30',
            'due_at' => '2026-12-31',
        ])->assertSessionHasErrors('starts_at');

        $this->assertDatabaseCount('billing_periods', 1);
    }

    public function test_administrator_cannot_change_rates_after_period_calculation(): void
    {
        $administrator = User::factory()->administrator()->create();
        $period = BillingPeriod::factory()->create([
            'status' => BillingPeriodStatus::Calculated,
            'calculated_at' => now(),
        ]);
        $rate = BillingRate::factory()->create([
            'billing_period_id' => $period->id,
            'code' => 'MEMBER_FEE',
        ]);

        $this->actingAs($administrator)
            ->get(route('billing-periods.billing-rates.edit', [$period, $rate]))
            ->assertForbidden();

        $this->actingAs($administrator)
            ->put(route('billing-periods.billing-rates.update', [$period, $rate]), [
                'code' => 'CHANGED',
                'name' => 'Geändert',
                'calculation_type' => 'fixed',
                'scope' => 'member',
                'amount' => '1.0000',
                'is_active' => '1',
            ])
            ->assertForbidden();

        $this->assertSame('MEMBER_FEE', $rate->fresh()->code);
    }
}
