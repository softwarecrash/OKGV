<?php

namespace Database\Factories;

use App\Enums\ParcelStatus;
use App\Enums\ParcelUseType;
use App\Models\Parcel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Parcel>
 */
class ParcelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'parcel_number' => fake()->unique()->numerify('P-###'),
            'area_sqm' => fake()->randomFloat(2, 100, 1000),
            'status' => ParcelStatus::Free,
            'use_type' => ParcelUseType::Lease,
            'has_operating_permit' => false,
            'location_description' => fake()->optional()->sentence(),
        ];
    }
}
