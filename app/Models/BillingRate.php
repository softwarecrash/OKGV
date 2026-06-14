<?php

namespace App\Models;

use App\Enums\BillingRateScope;
use App\Enums\BillingRateType;
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
    'amount',
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
            'amount' => 'decimal:4',
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
