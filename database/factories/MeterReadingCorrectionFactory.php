<?php

namespace Database\Factories;

use App\Models\MeterReading;
use App\Models\MeterReadingCorrection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeterReadingCorrection>
 */
class MeterReadingCorrectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'meter_reading_id' => MeterReading::factory(),
            'corrected_value' => fake()->randomFloat(4, 0, 10000),
            'reason' => fake()->sentence(),
            'corrected_by' => User::factory()->administrator(),
        ];
    }
}
