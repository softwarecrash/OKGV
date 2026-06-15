<?php

namespace App\Models;

use App\Enums\DunningNoticeStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'invoice_id',
    'notice_number',
    'level',
    'status',
    'invoice_number',
    'issued_at',
    'due_at',
    'invoice_amount',
    'fee_amount',
    'previous_fees_amount',
    'total_due',
    'recipients',
    'association_snapshot',
    'note',
    'created_by',
    'cancelled_at',
    'cancelled_by',
    'cancellation_reason',
])]
class DunningNotice extends Model
{
    protected static function booted(): void
    {
        static::updating(function (DunningNotice $notice): void {
            $allowedFields = [
                'status',
                'cancelled_at',
                'cancelled_by',
                'cancellation_reason',
                'updated_at',
            ];

            if (array_diff(array_keys($notice->getDirty()), $allowedFields) !== []) {
                throw new LogicException('Issued dunning notices are immutable.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('Dunning notices cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return [
            'status' => DunningNoticeStatus::class,
            'level' => 'integer',
            'issued_at' => 'date',
            'due_at' => 'date',
            'invoice_amount' => 'decimal:2',
            'fee_amount' => 'decimal:2',
            'previous_fees_amount' => 'decimal:2',
            'total_due' => 'decimal:2',
            'recipients' => 'array',
            'association_snapshot' => 'array',
            'cancelled_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
