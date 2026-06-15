<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable([
    'parcel_id',
    'outgoing_primary_tenancy_id',
    'incoming_primary_tenancy_id',
    'transfer_date',
    'outgoing_members_snapshot',
    'incoming_members_snapshot',
    'open_claims_snapshot',
    'notes',
    'completed_by',
    'completed_at',
])]
class TenantTransition extends Model
{
    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Completed tenant transitions are immutable.');
        });

        static::deleting(function (): never {
            throw new LogicException('Tenant transitions cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return [
            'transfer_date' => 'date',
            'outgoing_members_snapshot' => 'array',
            'incoming_members_snapshot' => 'array',
            'open_claims_snapshot' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class);
    }

    public function outgoingPrimaryTenancy(): BelongsTo
    {
        return $this->belongsTo(ParcelTenant::class, 'outgoing_primary_tenancy_id');
    }

    public function incomingPrimaryTenancy(): BelongsTo
    {
        return $this->belongsTo(ParcelTenant::class, 'incoming_primary_tenancy_id');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function meterReadings(): HasMany
    {
        return $this->hasMany(TenantTransitionMeterReading::class);
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'tenant_transition_documents')
            ->withPivot('category')
            ->withTimestamps();
    }
}
