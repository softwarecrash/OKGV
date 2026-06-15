<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'tenant_transition_id',
    'meter_id',
    'meter_reading_id',
    'reading_value',
])]
class TenantTransitionMeterReading extends Model
{
    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Tenant transition meter readings are immutable.');
        });

        static::deleting(function (): never {
            throw new LogicException('Tenant transition meter readings cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return [
            'reading_value' => 'decimal:4',
        ];
    }

    public function transition(): BelongsTo
    {
        return $this->belongsTo(TenantTransition::class, 'tenant_transition_id');
    }

    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class);
    }

    public function reading(): BelongsTo
    {
        return $this->belongsTo(MeterReading::class, 'meter_reading_id');
    }
}
