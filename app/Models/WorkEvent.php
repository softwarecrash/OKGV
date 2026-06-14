<?php

namespace App\Models;

use App\Enums\BillingPeriodStatus;
use App\Enums\WorkEventStatus;
use Database\Factories\WorkEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable([
    'billing_period_id',
    'title',
    'description',
    'location',
    'starts_at',
    'ends_at',
    'status',
    'notes',
    'created_by',
])]
class WorkEvent extends Model
{
    /** @use HasFactory<WorkEventFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::updating(function (WorkEvent $workEvent): void {
            $status = $workEvent->billingPeriod()->value('status');

            if (in_array($status, [
                BillingPeriodStatus::Approved,
                BillingPeriodStatus::Archived,
                BillingPeriodStatus::Approved->value,
                BillingPeriodStatus::Archived->value,
            ], true)) {
                throw new LogicException('Work events of approved billing periods cannot be changed.');
            }
        });

        static::deleting(function (): void {
            throw new LogicException('Work events cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => WorkEventStatus::class,
        ];
    }

    public function billingPeriod(): BelongsTo
    {
        return $this->belongsTo(BillingPeriod::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(WorkEventParticipant::class);
    }
}
