<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCreditorIdentifier implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $identifier = strtoupper(preg_replace('/\s+/', '', (string) $value));

        if (! preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{3}[A-Z0-9]{1,28}$/', $identifier)) {
            $fail('Die Gläubiger-ID hat kein gültiges SEPA-Format.');

            return;
        }

        $checkValue = substr($identifier, 7).substr($identifier, 0, 4);
        $numeric = '';

        foreach (str_split($checkValue) as $character) {
            $numeric .= ctype_alpha($character)
                ? (string) (ord($character) - 55)
                : $character;
        }

        $remainder = 0;
        foreach (str_split($numeric) as $digit) {
            $remainder = (($remainder * 10) + (int) $digit) % 97;
        }

        if ($remainder !== 1) {
            $fail('Die Prüfziffer der Gläubiger-ID ist nicht gültig.');
        }
    }
}
