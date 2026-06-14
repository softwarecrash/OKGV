<?php

namespace App\Models;

use App\Enums\MeterReadingSubmissionStatus;
use Database\Factories\MeterReadingSubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'meter_id',
    'submitted_by',
    'reading_value',
    'reading_date',
    'status',
    'photo_path',
    'photo_original_name',
    'photo_mime',
    'photo_size',
    'notes',
    'reviewed_by',
    'reviewed_at',
    'review_note',
    'meter_reading_id',
])]
class MeterReadingSubmission extends Model
{
    /** @use HasFactory<MeterReadingSubmissionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'reading_value' => 'decimal:4',
            'reading_date' => 'date',
            'status' => MeterReadingSubmissionStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function meterReading(): BelongsTo
    {
        return $this->belongsTo(MeterReading::class);
    }
}
