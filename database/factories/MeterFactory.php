<?php

namespace Database\Factories;

use App\Enums\MeterStatus;
use App\Enums\MeterType;
use App\Models\Meter;
use App\Models\Parcel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meter>
 */
class MeterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'parcel_id' => Parcel::factory(),
            'type' => fake()->randomElement(MeterType::cases()),
            'meter_number' => fake()->unique()->bothify('Z-####??'),
            'installed_at' => fake()->dateTimeBetween('-10 years', 'now'),
            'start_reading' => fake()->randomFloat(4, 0, 1000),
            'status' => MeterStatus::Active,
        ];
    }
}
