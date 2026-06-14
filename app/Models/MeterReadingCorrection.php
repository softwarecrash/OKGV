<?php

namespace App\Models;

use Database\Factories\MeterReadingCorrectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'meter_reading_id',
    'corrected_value',
    'reason',
    'corrected_by',
])]
class MeterReadingCorrection extends Model
{
    /** @use HasFactory<MeterReadingCorrectionFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException(
            'Meter reading corrections cannot be changed.',
        ));
        static::deleting(fn () => throw new LogicException(
            'Meter reading corrections cannot be deleted.',
        ));
    }

    protected function casts(): array
    {
        return [
            'corrected_value' => 'decimal:4',
            'created_at' => 'datetime',
        ];
    }

    public function meterReading(): BelongsTo
    {
        return $this->belongsTo(MeterReading::class);
    }

    public function corrector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }
}
