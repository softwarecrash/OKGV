<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'creditor_name',
    'creditor_identifier',
    'iban',
    'iban_last_four',
    'bic',
    'batch_booking',
    'message_version',
])]
class SepaSetting extends Model
{
    protected function casts(): array
    {
        return [
            'iban' => 'encrypted',
            'bic' => 'encrypted',
            'batch_booking' => 'boolean',
        ];
    }

    protected function maskedIban(): Attribute
    {
        return Attribute::get(fn (): string => '•••• '.($this->iban_last_four ?: '----'));
    }
}
