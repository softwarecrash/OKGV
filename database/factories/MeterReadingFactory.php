<?php

namespace Database\Factories;

use App\Enums\MeterReadingSource;
use App\Models\Meter;
use App\Models\MeterReading;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeterReading>
 */
class MeterReadingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'meter_id' => Meter::factory(),
            'reading_value' => fake()->randomFloat(4, 0, 10000),
            'reading_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'source' => MeterReadingSource::Board,
        ];
    }
}
