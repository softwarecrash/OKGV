<?php

namespace Database\Factories;

use App\Enums\BillingRateScope;
use App\Enums\BillingRateType;
use App\Models\BillingRateTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillingRateTemplate>
 */
class BillingRateTemplateFactory extends Factory
{
    protected $model = BillingRateTemplate::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->regexify('[A-Z]{6}_[A-Z]{4}'),
            'name' => fake()->words(3, true),
            'calculation_type' => BillingRateType::Fixed,
            'scope' => BillingRateScope::Member,
            'default_amount' => fake()->randomFloat(4, 1, 500),
            'is_active' => true,
        ];
    }
}
