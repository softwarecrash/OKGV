<?php

namespace App\Models;

use Database\Factories\ParcelTenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'parcel_id',
    'member_id',
    'starts_at',
    'ends_at',
    'is_primary',
    'notes',
])]
class ParcelTenant extends Model
{
    /** @use HasFactory<ParcelTenantFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_primary' => 'boolean',
        ];
    }

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function scopeActiveOn(Builder $query, mixed $date = null): Builder
    {
        $date ??= now()->toDateString();

        return $query
            ->whereDate('starts_at', '<=', $date)
            ->where(function (Builder $query) use ($date): void {
                $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', $date);
            });
    }
}
