<?php

namespace Database\Factories;

use App\Enums\PaymentBatchStatus;
use App\Models\PaymentBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentBatch>
 */
class PaymentBatchFactory extends Factory
{
    protected $model = PaymentBatch::class;

    public function definition(): array
    {
        return [
            'message_id' => fake()->unique()->bothify('OKGV-########-####'),
            'requested_collection_date' => now()->addDays(7)->toDateString(),
            'status' => PaymentBatchStatus::Draft,
            'creditor_name' => 'Kleingartenverein Beispiel',
            'creditor_identifier' => 'DE98ZZZ09999999999',
            'creditor_iban' => 'DE89370400440532013000',
            'batch_booking' => true,
            'message_version' => 'pain.008.001.08',
            'created_by' => User::factory()->administrator(),
        ];
    }
}
