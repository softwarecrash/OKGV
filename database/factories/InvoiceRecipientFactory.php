<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceRecipient;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceRecipient>
 */
class InvoiceRecipientFactory extends Factory
{
    public function definition(): array
    {
        $member = Member::factory()->make();

        return [
            'invoice_id' => Invoice::factory(),
            'member_id' => null,
            'member_number' => $member->member_number,
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'street' => $member->street,
            'zip' => $member->zip,
            'city' => $member->city,
            'is_primary' => false,
            'position' => 1,
        ];
    }
}
