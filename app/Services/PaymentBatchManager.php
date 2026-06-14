<?php

namespace App\Services;

use App\Enums\InvoicePaymentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentBatchItemStatus;
use App\Enums\PaymentBatchStatus;
use App\Enums\SepaMandateStatus;
use App\Enums\SepaMandateType;
use App\Models\Invoice;
use App\Models\PaymentBatch;
use App\Models\PaymentBatchItem;
use App\Models\SepaMandate;
use App\Models\SepaSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PaymentBatchManager
{
    /**
     * @param  array<int, int>  $invoiceIds
     */
    public function create(
        array $invoiceIds,
        string $collectionDate,
        User $actor,
    ): PaymentBatch {
        return DB::transaction(function () use ($invoiceIds, $collectionDate, $actor): PaymentBatch {
            $settings = SepaSetting::query()->first();

            if (! $settings) {
                throw ValidationException::withMessages([
                    'sepa_settings' => 'Bitte hinterlege zuerst die SEPA-Daten des Vereins.',
                ]);
            }

            $invoices = Invoice::query()
                ->whereKey($invoiceIds)
                ->lockForUpdate()
                ->get();

            if ($invoices->count() !== count(array_unique($invoiceIds))) {
                throw ValidationException::withMessages([
                    'invoice_ids' => 'Mindestens eine ausgewählte Rechnung ist nicht mehr verfügbar.',
                ]);
            }

            $batch = PaymentBatch::create([
                'message_id' => $this->messageId(),
                'requested_collection_date' => $collectionDate,
                'status' => PaymentBatchStatus::Draft,
                'creditor_name' => $settings->creditor_name,
                'creditor_identifier' => $settings->creditor_identifier,
                'creditor_iban' => $settings->iban,
                'creditor_bic' => $settings->bic,
                'batch_booking' => $settings->batch_booking,
                'message_version' => $settings->message_version,
                'created_by' => $actor->id,
            ]);

            $total = '0.00';

            foreach ($invoices as $invoice) {
                $mandate = $this->validateInvoice($invoice, $collectionDate);
                $sequenceType = $mandate->mandate_type === SepaMandateType::OneOff
                    ? 'OOFF'
                    : ($mandate->last_used_at ? 'RCUR' : 'FRST');

                $batch->items()->create([
                    'invoice_id' => $invoice->id,
                    'sepa_mandate_id' => $mandate->id,
                    'end_to_end_id' => $this->endToEndId($invoice),
                    'amount' => $invoice->total_amount,
                    'sequence_type' => $sequenceType,
                    'debtor_name' => mb_substr($mandate->account_holder, 0, 70),
                    'debtor_iban' => $mandate->iban,
                    'debtor_bic' => $mandate->bic,
                    'mandate_reference' => $mandate->mandate_reference,
                    'mandate_signed_at' => $mandate->signed_at,
                    'remittance_information' => mb_substr(
                        "Rechnung {$invoice->invoice_number}",
                        0,
                        140,
                    ),
                ]);

                $invoice->update(['payment_status' => InvoicePaymentStatus::Pending]);
                $total = bcadd($total, $invoice->total_amount, 2);
            }

            $batch->update([
                'item_count' => $invoices->count(),
                'control_sum' => $total,
            ]);

            AuditLogger::log(
                'payment.batch.created',
                $actor,
                $batch,
                ['item_count' => $invoices->count()],
            );

            return $batch->load(['items.invoice', 'items.mandate']);
        });
    }

    public function markExported(
        PaymentBatch $batch,
        string $xml,
        User $actor,
    ): PaymentBatch {
        return DB::transaction(function () use ($batch, $xml, $actor): PaymentBatch {
            $batch = PaymentBatch::query()->lockForUpdate()->findOrFail($batch->id);

            if (! in_array($batch->status, [
                PaymentBatchStatus::Draft,
                PaymentBatchStatus::Exported,
            ], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Nur vorbereitete oder bereits exportierte Sammler können heruntergeladen werden.',
                ]);
            }

            $batch->update([
                'status' => PaymentBatchStatus::Exported,
                'exported_at' => $batch->exported_at ?? now(),
                'xml_sha256' => hash('sha256', $xml),
            ]);

            AuditLogger::log('payment.batch.exported', $actor, $batch, [
                'xml_sha256' => $batch->xml_sha256,
            ]);

            return $batch;
        });
    }

    public function markSubmitted(PaymentBatch $batch, User $actor): PaymentBatch
    {
        return DB::transaction(function () use ($batch, $actor): PaymentBatch {
            $batch = PaymentBatch::query()->lockForUpdate()->findOrFail($batch->id);

            if ($batch->status !== PaymentBatchStatus::Exported) {
                throw ValidationException::withMessages([
                    'status' => 'Der Sammler muss vor der Einreichung exportiert werden.',
                ]);
            }

            $batch->items()->with('mandate')->get()->each(function (PaymentBatchItem $item): void {
                $item->update(['status' => PaymentBatchItemStatus::Submitted]);
                $item->mandate->update([
                    'last_used_at' => now(),
                    'status' => $item->mandate->mandate_type === SepaMandateType::OneOff
                        ? SepaMandateStatus::Expired
                        : $item->mandate->status,
                ]);
            });

            $batch->update([
                'status' => PaymentBatchStatus::Submitted,
                'submitted_at' => now(),
            ]);

            AuditLogger::log('payment.batch.submitted', $actor, $batch);

            return $batch;
        });
    }

    public function markSettled(PaymentBatch $batch, User $actor): PaymentBatch
    {
        return DB::transaction(function () use ($batch, $actor): PaymentBatch {
            $batch = PaymentBatch::query()->lockForUpdate()->findOrFail($batch->id);

            if (! in_array($batch->status, [
                PaymentBatchStatus::Submitted,
                PaymentBatchStatus::PartiallyReturned,
            ], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Nur eingereichte Sammellastschriften können als gebucht markiert werden.',
                ]);
            }

            $batch->items()
                ->where('status', '!=', PaymentBatchItemStatus::Returned->value)
                ->with('invoice')
                ->get()
                ->each(function (PaymentBatchItem $item): void {
                    $item->update(['status' => PaymentBatchItemStatus::Settled]);
                    $item->invoice->update([
                        'payment_status' => InvoicePaymentStatus::Paid,
                        'paid_at' => now(),
                    ]);
                });

            $hasReturns = $batch->items()
                ->where('status', PaymentBatchItemStatus::Returned)
                ->exists();

            $batch->update([
                'status' => $hasReturns
                    ? PaymentBatchStatus::PartiallyReturned
                    : PaymentBatchStatus::Settled,
                'settled_at' => now(),
            ]);

            AuditLogger::log('payment.batch.settled', $actor, $batch);

            return $batch;
        });
    }

    public function markReturned(
        PaymentBatchItem $item,
        string $reasonCode,
        ?string $reasonText,
        string $returnedAt,
        User $actor,
    ): PaymentBatchItem {
        return DB::transaction(function () use ($item, $reasonCode, $reasonText, $returnedAt, $actor): PaymentBatchItem {
            $item = PaymentBatchItem::query()->lockForUpdate()->findOrFail($item->id);

            if (! in_array($item->batch->status, [
                PaymentBatchStatus::Submitted,
                PaymentBatchStatus::Settled,
                PaymentBatchStatus::PartiallyReturned,
            ], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Eine Rücklastschrift kann erst nach der Einreichung erfasst werden.',
                ]);
            }

            if ($item->status === PaymentBatchItemStatus::Returned) {
                throw ValidationException::withMessages([
                    'status' => 'Diese Lastschrift wurde bereits als zurückgegeben markiert.',
                ]);
            }

            $item->update([
                'status' => PaymentBatchItemStatus::Returned,
                'return_reason_code' => $reasonCode,
                'return_reason_text' => $reasonText,
                'returned_at' => $returnedAt,
            ]);
            $item->invoice->update([
                'payment_status' => InvoicePaymentStatus::Returned,
                'paid_at' => null,
            ]);
            $item->batch->update(['status' => PaymentBatchStatus::PartiallyReturned]);

            AuditLogger::log('payment.item.returned', $actor, $item, [
                'reason_code' => $reasonCode,
            ]);

            return $item;
        });
    }

    private function validateInvoice(Invoice $invoice, string $collectionDate): SepaMandate
    {
        if ($invoice->status !== InvoiceStatus::Approved) {
            throw ValidationException::withMessages([
                'invoice_ids' => "Rechnung {$invoice->invoice_number} ist noch nicht freigegeben.",
            ]);
        }

        if (! in_array($invoice->payment_status, [
            InvoicePaymentStatus::Open,
            InvoicePaymentStatus::Returned,
        ], true)) {
            throw ValidationException::withMessages([
                'invoice_ids' => "Rechnung {$invoice->invoice_number} ist nicht offen.",
            ]);
        }

        $mandate = $invoice->member->sepaMandates()
            ->where('status', 'active')
            ->latest('valid_from')
            ->get()
            ->first(fn (SepaMandate $mandate): bool => $mandate->isUsableOn($collectionDate));

        if (! $mandate) {
            throw ValidationException::withMessages([
                'invoice_ids' => "Für {$invoice->member->full_name} ist am Einzugstag kein aktives SEPA-Mandat vorhanden.",
            ]);
        }

        return $mandate;
    }

    private function messageId(): string
    {
        return 'OKGV-'.now()->format('YmdHis').'-'.strtoupper(str()->random(6));
    }

    private function endToEndId(Invoice $invoice): string
    {
        return mb_substr('INV-'.$invoice->invoice_number.'-'.$invoice->id, 0, 35);
    }
}
