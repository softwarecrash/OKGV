<?php

namespace App\Models;

use App\Enums\PaymentBatchItemStatus;
use App\Enums\PaymentBatchStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'payment_batch_id',
    'invoice_id',
    'sepa_mandate_id',
    'end_to_end_id',
    'amount',
    'sequence_type',
    'status',
    'debtor_name',
    'debtor_iban',
    'debtor_bic',
    'mandate_reference',
    'mandate_signed_at',
    'remittance_information',
    'return_reason_code',
    'return_reason_text',
    'returned_at',
])]
class PaymentBatchItem extends Model
{
    protected static function booted(): void
    {
        static::updating(function (PaymentBatchItem $item): void {
            $allowedStatusFields = [
                'status',
                'return_reason_code',
                'return_reason_text',
                'returned_at',
                'updated_at',
            ];

            if ($item->batch()->value('status') !== PaymentBatchStatus::Draft->value
                && array_diff(array_keys($item->getDirty()), $allowedStatusFields) !== []) {
                throw new LogicException('Exported payment snapshots cannot be changed.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PaymentBatchItemStatus::class,
            'debtor_iban' => 'encrypted',
            'debtor_bic' => 'encrypted',
            'mandate_signed_at' => 'date',
            'returned_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PaymentBatch::class, 'payment_batch_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function mandate(): BelongsTo
    {
        return $this->belongsTo(SepaMandate::class, 'sepa_mandate_id');
    }
}
