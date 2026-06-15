<?php

namespace Database\Factories;

use App\Enums\WaitingListStatus;
use App\Models\WaitingListEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaitingListEntry>
 */
class WaitingListEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'mobile' => fake()->optional()->phoneNumber(),
            'applied_at' => fake()->dateTimeBetween('-2 years', 'now'),
            'priority' => fake()->numberBetween(1, 5),
            'status' => WaitingListStatus::Waiting,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
