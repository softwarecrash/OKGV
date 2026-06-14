<?php

namespace App\Models;

use App\Enums\BillingPeriodStatus;
use Database\Factories\BillingPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'starts_at',
    'ends_at',
    'due_at',
    'status',
    'calculated_at',
    'approved_at',
    'archived_at',
])]
class BillingPeriod extends Model
{
    /** @use HasFactory<BillingPeriodFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'due_at' => 'date',
            'status' => BillingPeriodStatus::class,
            'calculated_at' => 'datetime',
            'approved_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function rates(): HasMany
    {
        return $this->hasMany(BillingRate::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function workHours(): HasMany
    {
        return $this->hasMany(WorkHour::class);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [
            BillingPeriodStatus::Draft,
            BillingPeriodStatus::Calculated,
        ], true);
    }

    public function canBeCalculated(): bool
    {
        return $this->isEditable();
    }
}
