<?php

namespace App\Models;

use App\Enums\BillingRateScope;
use App\Enums\BillingRateType;
use App\Enums\BillingSettlementType;
use Database\Factories\BillingRateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'billing_period_id',
    'billing_rate_template_id',
    'code',
    'name',
    'description',
    'calculation_type',
    'scope',
    'settlement_type',
    'service_starts_at',
    'service_ends_at',
    'amount',
    'prorate',
    'is_active',
])]
class BillingRate extends Model
{
    /** @use HasFactory<BillingRateFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'calculation_type' => BillingRateType::class,
            'scope' => BillingRateScope::class,
            'settlement_type' => BillingSettlementType::class,
            'service_starts_at' => 'date',
            'service_ends_at' => 'date',
            'amount' => 'decimal:4',
            'prorate' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function billingPeriod(): BelongsTo
    {
        return $this->belongsTo(BillingPeriod::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(BillingRateTemplate::class, 'billing_rate_template_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(BillingRateAssignment::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
