<?php

namespace Database\Factories;

use App\Enums\WorkEventStatus;
use App\Models\BillingPeriod;
use App\Models\User;
use App\Models\WorkEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkEvent>
 */
class WorkEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'billing_period_id' => BillingPeriod::factory(),
            'title' => fake()->sentence(3),
            'location' => fake()->optional()->streetName(),
            'starts_at' => '2025-05-10 09:00:00',
            'ends_at' => '2025-05-10 13:00:00',
            'status' => WorkEventStatus::Planned,
            'created_by' => User::factory(),
        ];
    }
}
