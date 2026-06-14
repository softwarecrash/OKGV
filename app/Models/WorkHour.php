<?php

namespace App\Models;

use App\Enums\BillingPeriodStatus;
use Database\Factories\WorkHourFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'billing_period_id',
    'member_id',
    'hours_required',
    'hours_done',
    'hours_missing',
    'penalty_rate',
    'penalty_amount',
    'notes',
])]
class WorkHour extends Model
{
    /** @use HasFactory<WorkHourFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::updating(function (WorkHour $workHour): void {
            $status = $workHour->billingPeriod()->value('status');

            if (in_array($status, [
                BillingPeriodStatus::Approved,
                BillingPeriodStatus::Archived,
                BillingPeriodStatus::Approved->value,
                BillingPeriodStatus::Archived->value,
            ], true)) {
                throw new LogicException('Work hours of approved billing periods cannot be changed.');
            }
        });

        static::deleting(function (): void {
            throw new LogicException('Work hour records cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return [
            'hours_required' => 'decimal:2',
            'hours_done' => 'decimal:2',
            'hours_missing' => 'decimal:2',
            'penalty_rate' => 'decimal:2',
            'penalty_amount' => 'decimal:2',
        ];
    }

    public function billingPeriod(): BelongsTo
    {
        return $this->belongsTo(BillingPeriod::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
