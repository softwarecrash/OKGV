<?php

namespace App\Models;

use App\Enums\ParcelStatus;
use Database\Factories\ParcelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'parcel_number',
    'area_sqm',
    'status',
    'location_description',
    'map_x',
    'map_y',
    'map_width',
    'map_height',
    'map_polygon',
    'notes',
])]
class Parcel extends Model
{
    /** @use HasFactory<ParcelFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'area_sqm' => 'decimal:2',
            'status' => ParcelStatus::class,
            'map_x' => 'integer',
            'map_y' => 'integer',
            'map_width' => 'integer',
            'map_height' => 'integer',
            'map_polygon' => 'array',
        ];
    }

    public function tenancies(): HasMany
    {
        return $this->hasMany(ParcelTenant::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'parcel_tenants')
            ->withPivot(['id', 'starts_at', 'ends_at', 'is_primary', 'notes'])
            ->withTimestamps();
    }

    public function meters(): HasMany
    {
        return $this->hasMany(Meter::class);
    }

    public function billingRateAssignments(): HasMany
    {
        return $this->hasMany(BillingRateAssignment::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function workHours(): HasMany
    {
        return $this->hasMany(WorkHour::class);
    }

    public function workEventParticipations(): HasMany
    {
        return $this->hasMany(WorkEventParticipant::class);
    }

    public function workHourSubmissions(): HasMany
    {
        return $this->hasMany(WorkHourSubmission::class);
    }

    public function tenantTransitions(): HasMany
    {
        return $this->hasMany(TenantTransition::class);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search): void {
            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('parcel_number', 'like', "%{$search}%")
                    ->orWhere('location_description', 'like', "%{$search}%");
            });
        });
    }

    public function isPlacedOnMap(): bool
    {
        return is_array($this->map_polygon)
            && count($this->map_polygon) >= 3;
    }
}
