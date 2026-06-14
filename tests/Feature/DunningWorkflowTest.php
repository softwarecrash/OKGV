<?php

namespace Tests\Feature;

use App\Enums\DunningNoticeStatus;
use App\Enums\InvoicePaymentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Models\DunningNotice;
use App\Models\Invoice;
use App\Models\InvoiceRecipient;
use App\Models\Member;
use App\Models\User;
use App\Services\ActionIndicatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Tests\TestCase;

class DunningWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_three_levels_are_sequential_and_fees_accumulate(): void
    {
        Carbon::setTestNow('2026-03-01 10:00:00');
        $administrator = User::factory()->administrator()->create();
        $invoice = $this->approvedInvoice();
        $this->assertSame(
            1,
            app(ActionIndicatorService::class)->forUser($administrator)['dunning_notices'],
        );

        $this->actingAs($administrator)
            ->post(route('invoices.dunning-notices.store', $invoice), [
                'due_at' => '2026-03-10',
                'fee_amount' => '2.50',
                'note' => 'Erste Frist',
            ])
            ->assertRedirect();

        $first = DunningNotice::query()->firstOrFail();
        $this->assertSame(1, $first->level);
        $this->assertSame('2.50', $first->fee_amount);
        $this->assertSame('102.50', $first->total_due);
        $this->assertSame(
            0,
            app(ActionIndicatorService::class)->forUser($administrator)['dunning_notices'],
        );

        $this->actingAs($administrator)
            ->post(route('invoices.dunning-notices.store', $invoice), [
                'due_at' => '2026-03-20',
                'fee_amount' => '3.00',
            ])
            ->assertSessionHasErrors('due_at');

        Carbon::setTestNow('2026-03-11 10:00:00');
        $this->assertSame(
            1,
            app(ActionIndicatorService::class)->forUser($administrator)['dunning_notices'],
        );
        $this->actingAs($administrator)
            ->post(route('invoices.dunning-notices.store', $invoice), [
                'due_at' => '2026-03-20',
                'fee_amount' => '3.00',
            ])
            ->assertRedirect();

        $second = DunningNotice::query()->where('level', 2)->firstOrFail();
        $this->assertSame('2.50', $second->previous_fees_amount);
        $this->assertSame('105.50', $second->total_due);

        Carbon::setTestNow('2026-03-21 10:00:00');
        $this->actingAs($administrator)
            ->post(route('invoices.dunning-notices.store', $invoice), [
                'due_at' => '2026-03-31',
                'fee_amount' => '4.50',
            ])
            ->assertRedirect();

        $third = DunningNotice::query()->where('level', 3)->firstOrFail();
        $this->assertSame('5.50', $third->previous_fees_amount);
        $this->assertSame('110.00', $third->total_due);

        Carbon::setTestNow('2026-04-01 10:00:00');
        $this->actingAs($administrator)
            ->post(route('invoices.dunning-notices.store', $invoice), [
                'due_at' => '2026-04-15',
                'fee_amount' => '5.00',
            ])
            ->assertSessionHasErrors('invoice');

        $this->assertDatabaseCount('dunning_notices', 3);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'dunning_notice.created',
            'subject_id' => $third->id,
        ]);
    }

    public function test_only_latest_level_can_be_cancelled_and_same_level_can_be_reissued(): void
    {
        Carbon::setTestNow('2026-03-01 10:00:00');
        $administrator = User::factory()->administrator()->create();
        $invoice = $this->approvedInvoice();
        $first = $this->createNotice($administrator, $invoice, '2026-03-05', '2.00');

        Carbon::setTestNow('2026-03-06 10:00:00');
        $second = $this->createNotice($administrator, $invoice, '2026-03-10', '3.00');

        $this->actingAs($administrator)
            ->patch(route('dunning-notices.cancel', $first), [
                'cancellation_reason' => 'Falsche Stufe',
            ])
            ->assertSessionHasErrors('cancellation_reason');

        $this->actingAs($administrator)
            ->patch(route('dunning-notices.cancel', $second), [
                'cancellation_reason' => 'Gebühr wurde falsch eingetragen.',
            ])
            ->assertRedirect(route('dunning-notices.show', $second));

        $this->assertSame(DunningNoticeStatus::Cancelled, $second->fresh()->status);

        $replacement = $this->createNotice($administrator, $invoice, '2026-03-12', '1.50');
        $this->assertSame(2, $replacement->level);
        $this->assertSame('2.00', $replacement->previous_fees_amount);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'dunning_notice.cancelled',
            'subject_id' => $second->id,
        ]);
    }

    public function test_paid_future_and_unauthorized_invoices_cannot_be_dunned(): void
    {
        Carbon::setTestNow('2026-03-01 10:00:00');
        $administrator = User::factory()->administrator()->create();
        $boardWithoutBilling = User::factory()->create([
            'role' => UserRole::Board,
            'permissions' => [UserPermission::ViewAllMasterData->value],
        ]);
        $future = $this->approvedInvoice(['due_at' => '2026-03-10']);
        $paid = $this->approvedInvoice([
            'invoice_number' => '2026-PAID',
            'payment_status' => InvoicePaymentStatus::Paid,
            'paid_at' => now(),
        ]);

        $this->actingAs($administrator)
            ->post(route('invoices.dunning-notices.store', $future), [
                'due_at' => '2026-03-20',
                'fee_amount' => '0',
            ])
            ->assertSessionHasErrors('invoice');

        $this->actingAs($administrator)
            ->post(route('invoices.dunning-notices.store', $paid), [
                'due_at' => '2026-03-20',
                'fee_amount' => '0',
            ])
            ->assertSessionHasErrors('invoice');

        $this->actingAs($boardWithoutBilling)
            ->get(route('dunning-notices.index'))
            ->assertForbidden();
        $this->actingAs($boardWithoutBilling)
            ->post(route('invoices.dunning-notices.store', $future), [
                'due_at' => '2026-03-20',
                'fee_amount' => '0',
            ])
            ->assertForbidden();
    }

    public function test_tenant_can_only_read_own_notice_and_pdf(): void
    {
        Carbon::setTestNow('2026-03-01 10:00:00');
        $administrator = User::factory()->administrator()->create();
        $tenant = User::factory()->create(['role' => UserRole::Tenant]);
        $foreignTenant = User::factory()->create(['role' => UserRole::Tenant]);
        $member = Member::factory()->create(['user_id' => $tenant->id]);
        Member::factory()->create(['user_id' => $foreignTenant->id]);
        $invoice = $this->approvedInvoice(['member_id' => $member->id], $member);
        $notice = $this->createNotice($administrator, $invoice, '2026-03-15', '2.50');

        $this->actingAs($tenant)
            ->get(route('dunning-notices.show', $notice))
            ->assertOk()
            ->assertSee($notice->notice_number);
        $this->actingAs($tenant)
            ->get(route('dunning-notices.pdf', $notice))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($foreignTenant)
            ->get(route('dunning-notices.show', $notice))
            ->assertForbidden();
        $this->actingAs($tenant)
            ->get(route('dunning-notices.index'))
            ->assertForbidden();
    }

    public function test_issued_notice_is_immutable_and_cannot_be_deleted(): void
    {
        Carbon::setTestNow('2026-03-01 10:00:00');
        $administrator = User::factory()->administrator()->create();
        $notice = $this->createNotice(
            $administrator,
            $this->approvedInvoice(),
            '2026-03-15',
            '2.50',
        );

        try {
            $notice->update(['fee_amount' => 99]);
            $this->fail('Eine ausgestellte Mahnung durfte verändert werden.');
        } catch (LogicException) {
            $this->assertSame('2.50', $notice->fresh()->fee_amount);
        }

        $this->expectException(LogicException::class);
        $notice->delete();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function approvedInvoice(array $attributes = [], ?Member $member = null): Invoice
    {
        $member ??= Member::factory()->create();
        $invoice = Invoice::factory()->create([
            'member_id' => $member->id,
            'invoice_number' => '2026-'.fake()->unique()->numerify('#####'),
            'status' => InvoiceStatus::Draft,
            'payment_status' => InvoicePaymentStatus::Open,
            'issued_at' => '2026-02-01',
            'due_at' => '2026-02-15',
            'total_amount' => 100,
            ...$attributes,
        ]);
        InvoiceRecipient::factory()->create([
            'invoice_id' => $invoice->id,
            'member_id' => $member->id,
            'member_number' => $member->member_number,
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'street' => $member->street,
            'zip' => $member->zip,
            'city' => $member->city,
            'is_primary' => true,
        ]);
        $invoice->update([
            'status' => InvoiceStatus::Approved,
            'approved_at' => now(),
        ]);

        return $invoice->refresh();
    }

    private function createNotice(
        User $actor,
        Invoice $invoice,
        string $dueAt,
        string $fee,
    ): DunningNotice {
        $this->actingAs($actor)
            ->post(route('invoices.dunning-notices.store', $invoice), [
                'due_at' => $dueAt,
                'fee_amount' => $fee,
            ])
            ->assertRedirect();

        return DunningNotice::query()->latest('id')->firstOrFail();
    }
}
