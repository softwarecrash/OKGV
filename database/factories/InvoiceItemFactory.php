<?php

namespace Database\Factories;

use App\Enums\BillingRateType;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = fake()->randomFloat(4, 1, 10);
        $unitPrice = fake()->randomFloat(4, 1, 100);

        return [
            'invoice_id' => Invoice::factory(),
            'code' => fake()->unique()->regexify('[A-Z]{6}_[A-Z]{4}'),
            'description' => fake()->words(3, true),
            'calculation_type' => BillingRateType::Fixed,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_amount' => round($quantity * $unitPrice, 2),
        ];
    }
}
