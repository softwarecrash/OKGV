<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\BillingPeriod;
use App\Models\Invoice;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'billing_period_id' => BillingPeriod::factory(),
            'member_id' => Member::factory(),
            'invoice_number' => fake()->unique()->numerify('2026-#####'),
            'status' => InvoiceStatus::Draft,
            'issued_at' => now()->toDateString(),
            'due_at' => now()->addDays(30)->toDateString(),
            'total_amount' => 0,
        ];
    }
}
