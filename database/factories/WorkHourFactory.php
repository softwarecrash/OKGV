<?php

namespace Database\Factories;

use App\Models\BillingPeriod;
use App\Models\Parcel;
use App\Models\WorkHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkHour>
 */
class WorkHourFactory extends Factory
{
    public function definition(): array
    {
        $required = fake()->randomElement([5, 10, 15]);
        $done = fake()->numberBetween(0, $required);
        $missing = $required - $done;
        $rate = fake()->randomElement([10, 15, 20]);

        return [
            'billing_period_id' => BillingPeriod::factory(),
            'parcel_id' => Parcel::factory(),
            'hours_required' => number_format($required, 2, '.', ''),
            'manual_hours_done' => number_format($done, 2, '.', ''),
            'event_hours_done' => '0.00',
            'submission_hours_done' => '0.00',
            'hours_done' => number_format($done, 2, '.', ''),
            'hours_missing' => number_format($missing, 2, '.', ''),
            'penalty_rate' => number_format($rate, 2, '.', ''),
            'penalty_amount' => number_format($missing * $rate, 2, '.', ''),
        ];
    }
}
