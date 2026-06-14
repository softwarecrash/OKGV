<?php

namespace Database\Factories;

use App\Enums\RegistrationRequestStatus;
use App\Models\Parcel;
use App\Models\RegistrationRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistrationRequest>
 */
class RegistrationRequestFactory extends Factory
{
    protected $model = RegistrationRequest::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'parcel_id' => Parcel::factory(),
            'parcel_number' => fake()->unique()->numerify('P-###'),
            'password' => 'secure-password',
            'status' => RegistrationRequestStatus::Pending,
        ];
    }
}
