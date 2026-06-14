<?php

namespace App\Models;

use App\Enums\PaymentBatchStatus;
use Database\Factories\PaymentBatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable([
    'message_id',
    'requested_collection_date',
    'status',
    'item_count',
    'control_sum',
    'creditor_name',
    'creditor_identifier',
    'creditor_iban',
    'creditor_bic',
    'batch_booking',
    'message_version',
    'created_by',
    'exported_at',
    'submitted_at',
    'settled_at',
    'xml_sha256',
])]
class PaymentBatch extends Model
{
    /** @use HasFactory<PaymentBatchFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::updating(function (PaymentBatch $batch): void {
            $allowedLifecycleFields = [
                'status',
                'exported_at',
                'submitted_at',
                'settled_at',
                'xml_sha256',
                'updated_at',
            ];

            if ($batch->getRawOriginal('status') !== PaymentBatchStatus::Draft->value
                && array_diff(array_keys($batch->getDirty()), $allowedLifecycleFields) !== []) {
                throw new LogicException('Exported payment batch snapshots cannot be changed.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'requested_collection_date' => 'date',
            'status' => PaymentBatchStatus::class,
            'control_sum' => 'decimal:2',
            'creditor_iban' => 'encrypted',
            'creditor_bic' => 'encrypted',
            'batch_booking' => 'boolean',
            'exported_at' => 'datetime',
            'submitted_at' => 'datetime',
            'settled_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PaymentBatchItem::class);
    }
}
