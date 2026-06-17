<?php

namespace App\Models;

use App\Enums\WorkHourSubmissionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'billing_period_id',
    'parcel_id',
    'submitted_by',
    'worked_at',
    'hours',
    'description',
    'status',
    'photo_path',
    'photo_original_name',
    'photo_mime',
    'photo_size',
    'reviewed_by',
    'reviewed_at',
    'review_note',
    'tenant_acknowledged_at',
])]
class WorkHourSubmission extends Model
{
    protected static function booted(): void
    {
        static::updating(function (WorkHourSubmission $submission): void {
            if ($submission->getRawOriginal('status') !== WorkHourSubmissionStatus::Pending->value) {
                $allowedDirtyFields = ['tenant_acknowledged_at', 'updated_at'];

                if (array_diff(array_keys($submission->getDirty()), $allowedDirtyFields) === []) {
                    return;
                }

                throw new LogicException('Reviewed work hour submissions cannot be changed.');
            }
        });

        static::deleting(function (): void {
            throw new LogicException('Work hour submissions cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return [
            'worked_at' => 'date',
            'hours' => 'decimal:2',
            'status' => WorkHourSubmissionStatus::class,
            'reviewed_at' => 'datetime',
            'tenant_acknowledged_at' => 'datetime',
        ];
    }

    public function billingPeriod(): BelongsTo
    {
        return $this->belongsTo(BillingPeriod::class);
    }

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeUnresolvedRejectedForUser(Builder $query, int $userId): Builder
    {
        return $query
            ->where('submitted_by', $userId)
            ->where('status', WorkHourSubmissionStatus::Rejected)
            ->whereNull('tenant_acknowledged_at')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('work_hour_submissions as later_submission')
                    ->whereColumn(
                        'later_submission.submitted_by',
                        'work_hour_submissions.submitted_by',
                    )
                    ->whereColumn(
                        'later_submission.parcel_id',
                        'work_hour_submissions.parcel_id',
                    )
                    ->whereColumn(
                        'later_submission.id',
                        '>',
                        'work_hour_submissions.id',
                    );
            });
    }
}
