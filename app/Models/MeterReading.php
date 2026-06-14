<?php

namespace App\Models;

use App\Enums\MeterReadingSource;
use Database\Factories\MeterReadingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
