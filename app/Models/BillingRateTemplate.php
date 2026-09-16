<?php

namespace App\Models;

use App\Enums\BillingRateScope;
use App\Enums\BillingRateType;
use App\Enums\BillingSettlementType;
use Database\Factories\BillingRateTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'name',
    'description',
    'calculation_type',
    'scope',
    'applies_to_leased_parcels',
    'applies_to_owned_parcels',
    'settlement_type',
    'default_amount',
    'prorate',
    'is_active',
])]
class BillingRateTemplate extends Model
{
    /** @use HasFactory<BillingRateTemplateFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'calculation_type' => BillingRateType::class,
            'scope' => BillingRateScope::class,
            'applies_to_leased_parcels' => 'boolean',
            'applies_to_owned_parcels' => 'boolean',
            'settlement_type' => BillingSettlementType::class,
            'default_amount' => 'decimal:4',
            'prorate' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function rates(): HasMany
    {
        return $this->hasMany(BillingRate::class);
    }
}
