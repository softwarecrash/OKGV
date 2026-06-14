<?php

namespace App\Models;

use App\Enums\MemberStatus;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'member_number',
    'first_name',
    'last_name',
    'street',
    'zip',
    'city',
    'phone',
    'mobile',
    'email',
    'joined_at',
    'left_at',
    'status',
    'notes',
    'archived_at',
])]
class Member extends Model
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'joined_at' => 'date',
            'left_at' => 'date',
            'archived_at' => 'datetime',
            'status' => MemberStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parcelTenancies(): HasMany
    {
        return $this->hasMany(ParcelTenant::class);
    }

    public function billingRateAssignments(): HasMany
    {
        return $this->hasMany(BillingRateAssignment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function sepaMandates(): HasMany
    {
        return $this->hasMany(SepaMandate::class);
    }

    public function invoiceRecipientSnapshots(): HasMany
    {
        return $this->hasMany(InvoiceRecipient::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function workEventParticipations(): HasMany
    {
        return $this->hasMany(WorkEventParticipant::class);
    }

    public function parcels(): BelongsToMany
    {
        return $this->belongsToMany(Parcel::class, 'parcel_tenants')
            ->withPivot(['id', 'starts_at', 'ends_at', 'is_primary', 'notes'])
            ->withTimestamps();
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search): void {
            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('member_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        });
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
