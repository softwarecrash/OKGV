<?php

namespace Database\Factories;

use App\Enums\BillingRateScope;
use App\Enums\BillingRateType;
use App\Enums\BillingSettlementType;
use App\Models\BillingPeriod;
use App\Models\BillingRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillingRate>
 */
class BillingRateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'billing_period_id' => BillingPeriod::factory(),
            'code' => fake()->unique()->regexify('[A-Z]{6}_[A-Z]{4}'),
            'name' => fake()->words(3, true),
            'calculation_type' => BillingRateType::Fixed,
            'scope' => BillingRateScope::Member,
            'settlement_type' => BillingSettlementType::Arrears,
            'service_starts_at' => fn (array $attributes) => BillingPeriod::query()
                ->find($attributes['billing_period_id'])?->starts_at,
            'service_ends_at' => fn (array $attributes) => BillingPeriod::query()
                ->find($attributes['billing_period_id'])?->ends_at,
            'amount' => fake()->randomFloat(4, 1, 500),
            'prorate' => false,
            'is_active' => true,
        ];
    }
}
