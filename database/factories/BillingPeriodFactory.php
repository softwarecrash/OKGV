<?php

namespace Database\Factories;

use App\Enums\BillingPeriodStatus;
use App\Models\BillingPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillingPeriod>
 */
class BillingPeriodFactory extends Factory
{
    public function definition(): array
    {
        $year = fake()->unique()->numberBetween(2000, 2099);

        return [
            'name' => "Abrechnung {$year}",
            'starts_at' => "{$year}-01-01",
            'ends_at' => "{$year}-12-31",
            'due_at' => ($year + 1).'-02-01',
            'status' => BillingPeriodStatus::Draft,
        ];
    }
}
