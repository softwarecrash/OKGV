<?php

namespace App\Rules;

use Closure;
use Iban\Validation\Iban;
use Iban\Validation\Validator;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidIban implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $iban = new Iban((string) $value);

        if (! (new Validator)->validate($iban)) {
            $fail('Die IBAN ist nicht gültig. Bitte prüfe Länderkennung, Länge und Prüfziffern.');
        }
    }
}
