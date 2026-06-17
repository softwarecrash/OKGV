<?php

namespace Tests\Feature;

use App\Enums\InvoicePaymentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentBatchItemStatus;
use App\Enums\PaymentBatchStatus;
use App\Enums\SepaMandateStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\SepaMandate;
use App\Models\SepaSetting;
use App\Models\User;
use App\Services\Pain008Generator;
use App\Services\PaymentBatchManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class SepaWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_financial_roles_can_access_sepa_data(): void
    {
        foreach ([UserRole::Board, UserRole::Treasurer] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get(route('sepa-mandates.index'))->assertOk();
        }

        foreach ([UserRole::WaterManager, UserRole::GardenManager, UserRole::Tenant] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get(route('sepa-mandates.index'))->assertForbidden();
        }
    }

    public function test_sepa_settings_validate_and_encrypt_bank_data(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)
            ->put(route('sepa-settings.update'), [
                'creditor_name' => 'Kleingartenverein Beispiel',
                'creditor_identifier' => 'DE98ZZZ09999999999',
                'iban' => 'DE89 3704 0044 0532 0130 00',
                'bic' => 'COBADEFFXXX',
                'batch_booking' => '1',
            ])
            ->assertRedirect();

        $settings = SepaSetting::query()->firstOrFail();
        $this->assertSame('DE89370400440532013000', $settings->iban);
        $this->assertSame('3000', $settings->iban_last_four);
        $this->assertStringNotContainsString(
            'DE89370400440532013000',
            (string) $settings->getRawOriginal('iban'),
        );

        $this->actingAs($administrator)
            ->put(route('sepa-settings.update'), [
                'creditor_name' => 'Kleingartenverein Beispiel',
                'creditor_identifier' => 'DE00ZZZ09999999999',
                'iban' => 'DE001234',
                'batch_booking' => '1',
            ])
            ->assertSessionHasErrors(['creditor_identifier', 'iban']);
    }

    public function test_mandate_bank_data_is_encrypted_and_masked_in_list(): void
    {
        $treasurer = User::factory()->create(['role' => UserRole::Treasurer]);
        $member = Member::factory()->create();

        $this->actingAs($treasurer)
            ->post(route('sepa-mandates.store'), [
                'member_id' => $member->id,
                'mandate_reference' => 'MANDAT 2026',
                'iban' => 'DE89370400440532013000',
                'account_holder' => 'Erika Mustermann',
                'signed_at' => '2026-01-01',
                'valid_from' => '2026-01-01',
                'mandate_type' => 'recurring',
                'status' => 'active',
            ])
            ->assertRedirect(route('sepa-mandates.index'));

        $mandate = SepaMandate::query()->firstOrFail();
        $this->assertStringNotContainsString(
            'DE89370400440532013000',
            (string) $mandate->getRawOriginal('iban'),
        );
        $this->assertStringNotContainsString(
            'Erika Mustermann',
            (string) $mandate->getRawOriginal('account_holder'),
        );

        $this->actingAs($treasurer)
            ->get(route('sepa-mandates.index'))
            ->assertSee('•••• 3000')
            ->assertDontSee('DE89370400440532013000');
    }

    public function test_tenant_can_create_and_revoke_own_sepa_mandate(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::Tenant]);
        $member = Member::factory()->create(['user_id' => $tenant->id]);
        $treasurer = User::factory()->create(['role' => UserRole::Treasurer]);

        $this->actingAs($tenant)
            ->post(route('tenant-portal.sepa-mandates.store'), [
                'iban' => 'DE89 3704 0044 0532 0130 00',
                'account_holder' => 'Erika Mustermann',
                'consent' => '1',
            ])
            ->assertRedirect(route('tenant-portal.sepa-mandates.index'));

        $mandate = SepaMandate::query()->firstOrFail();
        $this->assertSame($member->id, $mandate->member_id);
        $this->assertSame($tenant->id, $mandate->created_by);
        $this->assertSame(SepaMandateStatus::Active, $mandate->status);
        $this->assertSame('3000', $mandate->iban_last_four);
        $this->assertStringNotContainsString(
            'DE89370400440532013000',
            (string) $mandate->getRawOriginal('iban'),
        );

        $this->actingAs($treasurer)
            ->get(route('sepa-mandates.index'))
            ->assertOk()
            ->assertSee($member->full_name)
            ->assertSee('Selbst hinterlegt');

        $this->actingAs($tenant)
            ->post(route('tenant-portal.sepa-mandates.revoke', $mandate), [
                'confirm_revoke' => '1',
                'revocation_note' => 'Bitte nicht mehr einziehen.',
            ])
            ->assertRedirect(route('tenant-portal.sepa-mandates.index'));

        $mandate->refresh();
        $this->assertSame(SepaMandateStatus::Revoked, $mandate->status);
        $this->assertNotNull($mandate->revoked_at);
        $this->assertSame($tenant->id, $mandate->revoked_by);
        $this->assertSame('Bitte nicht mehr einziehen.', $mandate->revocation_note);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'sepa.mandate.self_revoked',
            'subject_id' => $mandate->id,
        ]);
    }

    public function test_tenant_cannot_revoke_foreign_sepa_mandate(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::Tenant]);
        Member::factory()->create(['user_id' => $tenant->id]);
        $foreignMandate = SepaMandate::factory()->create();

        $this->actingAs($tenant)
            ->post(route('tenant-portal.sepa-mandates.revoke', $foreignMandate), [
                'confirm_revoke' => '1',
            ])
            ->assertForbidden();

        $this->assertSame(SepaMandateStatus::Active, $foreignMandate->fresh()->status);
    }

    public function test_batch_generates_pain_008_and_tracks_payment_lifecycle(): void
    {
        [$administrator, $invoice] = $this->sepaScenario();

        $batch = app(PaymentBatchManager::class)->create(
            [$invoice->id],
            '2026-07-01',
            $administrator,
        );

        $this->assertSame(InvoicePaymentStatus::Pending, $invoice->fresh()->payment_status);
        $this->assertSame('125.50', $batch->control_sum);
        $this->assertSame('FRST', $batch->items->first()->sequence_type);

        $xml = app(Pain008Generator::class)->generate($batch);
        $this->assertStringContainsString('pain.008.001.08', $xml);
        $this->assertStringContainsString('<Cd>CORE</Cd>', $xml);
        $this->assertStringContainsString('<SeqTp>FRST</SeqTp>', $xml);
        $this->assertStringContainsString('<MndtId>MANDATE-2026-001</MndtId>', $xml);
        $this->assertStringContainsString('<IBAN>DE89370400440532013000</IBAN>', $xml);
        $this->assertStringContainsString('Rechnung '.$invoice->invoice_number, $xml);
        $this->assertStringNotContainsString(
            'DE89370400440532013000',
            (string) $batch->getRawOriginal('creditor_iban'),
        );

        SepaSetting::query()->firstOrFail()->update([
            'creditor_name' => 'Geänderter Vereinsname',
            'creditor_identifier' => 'DE66ZZZ00000000001',
            'iban' => 'DE12500105170648489890',
            'iban_last_four' => '9890',
        ]);
        $this->assertSame($xml, app(Pain008Generator::class)->generate($batch->fresh()));

        app(PaymentBatchManager::class)->markExported($batch, $xml, $administrator);
        $this->assertThrows(
            fn () => $batch->fresh()->update(['creditor_name' => 'Manipulierter Name']),
            LogicException::class,
        );
        $this->assertThrows(
            fn () => app(PaymentBatchManager::class)->markSettled($batch->fresh(), $administrator),
            ValidationException::class,
        );
        app(PaymentBatchManager::class)->markSubmitted($batch->fresh(), $administrator);
        app(PaymentBatchManager::class)->markSettled($batch->fresh(), $administrator);

        $this->assertSame(PaymentBatchStatus::Settled, $batch->fresh()->status);
        $this->assertSame(InvoicePaymentStatus::Paid, $invoice->fresh()->payment_status);
        $this->assertNotNull($invoice->fresh()->paid_at);
    }

    public function test_return_reopens_invoice_and_preserves_original_item(): void
    {
        [$administrator, $invoice] = $this->sepaScenario();
        $batch = app(PaymentBatchManager::class)->create(
            [$invoice->id],
            '2026-07-01',
            $administrator,
        );
        $item = $batch->items->first();

        $this->assertThrows(
            fn () => app(PaymentBatchManager::class)->markReturned(
                $item,
                'AM04',
                null,
                '2026-07-03',
                $administrator,
            ),
            ValidationException::class,
        );
        $xml = app(Pain008Generator::class)->generate($batch);
        app(PaymentBatchManager::class)->markExported($batch, $xml, $administrator);
        app(PaymentBatchManager::class)->markSubmitted($batch->fresh(), $administrator);

        app(PaymentBatchManager::class)->markReturned(
            $item,
            'AM04',
            'Keine ausreichende Deckung',
            '2026-07-03',
            $administrator,
        );

        $this->assertSame(PaymentBatchItemStatus::Returned, $item->fresh()->status);
        $this->assertSame('AM04', $item->fresh()->return_reason_code);
        $this->assertSame(InvoicePaymentStatus::Returned, $invoice->fresh()->payment_status);
        $this->assertSame(PaymentBatchStatus::PartiallyReturned, $batch->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payment.item.returned',
            'subject_id' => $item->id,
        ]);
    }

    /**
     * @return array{User, Invoice}
     */
    private function sepaScenario(): array
    {
        $administrator = User::factory()->administrator()->create();
        $member = Member::factory()->create();
        $invoice = Invoice::factory()->create([
            'member_id' => $member->id,
            'status' => InvoiceStatus::Approved,
            'payment_status' => InvoicePaymentStatus::Open,
            'total_amount' => '125.50',
            'approved_at' => now(),
            'approved_by' => $administrator->id,
        ]);
        SepaSetting::create([
            'creditor_name' => 'Kleingartenverein Beispiel',
            'creditor_identifier' => 'DE98ZZZ09999999999',
            'iban' => 'DE89370400440532013000',
            'iban_last_four' => '3000',
            'batch_booking' => true,
            'message_version' => 'pain.008.001.08',
        ]);
        SepaMandate::factory()->create([
            'member_id' => $member->id,
            'mandate_reference' => 'MANDATE-2026-001',
            'account_holder' => 'Erika Mustermann',
            'valid_from' => '2026-01-01',
        ]);

        return [$administrator, $invoice];
    }
}
