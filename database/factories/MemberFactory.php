<?php

namespace Database\Factories;

use App\Enums\MemberStatus;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'member_number' => fake()->unique()->numerify('M-####'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'street' => fake()->streetAddress(),
            'zip' => fake()->postcode(),
            'city' => fake()->city(),
            'phone' => fake()->optional()->phoneNumber(),
            'mobile' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'joined_at' => fake()->dateTimeBetween('-20 years', 'now'),
            'status' => MemberStatus::Active,
        ];
    }
}
