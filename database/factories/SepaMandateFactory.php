<?php

namespace Database\Factories;

use App\Enums\SepaMandateStatus;
use App\Enums\SepaMandateType;
use App\Models\Member;
use App\Models\SepaMandate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SepaMandate>
 */
class SepaMandateFactory extends Factory
{
    protected $model = SepaMandate::class;

    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'mandate_reference' => fake()->unique()->bothify('MANDATE-####-????'),
            'iban' => 'DE89370400440532013000',
            'iban_last_four' => '3000',
            'account_holder' => fake()->name(),
            'signed_at' => '2025-01-01',
            'valid_from' => '2025-01-01',
            'mandate_type' => SepaMandateType::Recurring,
            'status' => SepaMandateStatus::Active,
        ];
    }
}
