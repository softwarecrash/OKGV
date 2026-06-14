<?php

namespace App\Models;

use App\Enums\BillingPeriodStatus;
use App\Enums\WorkEventParticipantStatus;
use Database\Factories\WorkEventParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'work_event_id',
    'member_id',
    'parcel_id',
    'status',
    'hours',
    'notes',
    'confirmed_by',
    'confirmed_at',
])]
class WorkEventParticipant extends Model
{
    /** @use HasFactory<WorkEventParticipantFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::updating(function (WorkEventParticipant $participant): void {
            $status = $participant->workEvent()
                ->with('billingPeriod')
                ->firstOrFail()
                ->billingPeriod
                ->status;

            if (in_array($status, [
                BillingPeriodStatus::Approved,
                BillingPeriodStatus::Archived,
            ], true)) {
                throw new LogicException('Participants of approved billing periods cannot be changed.');
            }
        });

        static::deleting(function (): void {
            throw new LogicException('Work event participants cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return [
            'status' => WorkEventParticipantStatus::class,
            'hours' => 'decimal:2',
            'confirmed_at' => 'datetime',
        ];
    }

    public function workEvent(): BelongsTo
    {
        return $this->belongsTo(WorkEvent::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class);
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
