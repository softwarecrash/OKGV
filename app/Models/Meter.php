<?php

namespace App\Models;

use App\Enums\MeterStatus;
use App\Enums\MeterType;
use Database\Factories\MeterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'parcel_id',
    'type',
    'meter_number',
    'installed_at',
    'removed_at',
    'start_reading',
    'end_reading',
    'status',
    'notes',
])]
class Meter extends Model
{
    /** @use HasFactory<MeterFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => MeterType::class,
            'status' => MeterStatus::class,
            'installed_at' => 'date',
            'removed_at' => 'date',
            'start_reading' => 'decimal:4',
            'end_reading' => 'decimal:4',
        ];
    }

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class);
    }

    public function readings(): HasMany
    {
        return $this->hasMany(MeterReading::class);
    }

    public function readingSubmissions(): HasMany
    {
        return $this->hasMany(MeterReadingSubmission::class);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search): void {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('meter_number', 'like', "%{$search}%")
                    ->orWhereHas('parcel', fn (Builder $query) => $query
                        ->where('parcel_number', 'like', "%{$search}%"));
            });
        });
    }
}
