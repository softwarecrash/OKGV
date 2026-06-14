<?php

namespace Database\Factories;

use App\Enums\MeterReadingSubmissionStatus;
use App\Enums\UserRole;
use App\Models\Meter;
use App\Models\MeterReadingSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeterReadingSubmission>
 */
class MeterReadingSubmissionFactory extends Factory
{
    protected $model = MeterReadingSubmission::class;

    public function definition(): array
    {
        return [
            'meter_id' => Meter::factory(),
            'submitted_by' => User::factory()->state(['role' => UserRole::Tenant]),
            'reading_value' => fake()->randomFloat(4, 0, 5000),
            'reading_date' => now()->toDateString(),
            'status' => MeterReadingSubmissionStatus::Pending,
        ];
    }
}
