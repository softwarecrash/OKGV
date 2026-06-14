<?php

namespace App\Models;

use App\Enums\MeterReadingSource;
use Database\Factories\MeterReadingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'meter_id',
    'reading_value',
    'reading_date',
    'source',
    'photo_path',
    'notes',
])]
class MeterReading extends Model
{
    /** @use HasFactory<MeterReadingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'reading_value' => 'decimal:4',
            'reading_date' => 'date',
            'source' => MeterReadingSource::class,
        ];
    }

    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class);
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(MeterReadingCorrection::class)->latest('id');
    }

    public function submission(): HasOne
    {
        return $this->hasOne(MeterReadingSubmission::class);
    }

    public function getEffectiveReadingValueAttribute(): string
    {
        return $this->corrections->first()?->corrected_value ?? $this->reading_value;
    }
}
