<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Parcel;
use App\Models\ParcelTenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParcelTenant>
 */
class ParcelTenantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'parcel_id' => Parcel::factory(),
            'member_id' => Member::factory(),
            'starts_at' => fake()->dateTimeBetween('-10 years', 'now'),
            'is_primary' => true,
        ];
    }
}
