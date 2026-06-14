<?php

namespace Database\Factories;

use App\Models\BillingRate;
use App\Models\BillingRateAssignment;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillingRateAssignment>
 */
class BillingRateAssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'billing_rate_id' => BillingRate::factory(),
            'member_id' => Member::factory(),
            'quantity' => 1,
        ];
    }
}
