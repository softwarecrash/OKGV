<?php

namespace App\Http\Requests;

use App\Rules\ValidIban;
use Illuminate\Foundation\Http\FormRequest;

class PortalSepaMandateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasTenantAccess() ?? false;
    }

    public function rules(): array
    {
        return [
            'iban' => ['required', 'string', 'max:42', new ValidIban],
            'bic' => ['nullable', 'string', 'regex:/^[A-Z0-9]{8}([A-Z0-9]{3})?$/'],
            'account_holder' => ['required', 'string', 'max:70'],
            'consent' => ['accepted'],
        ];
    }

    public function attributes(): array
    {
        return [
            'iban' => 'IBAN',
            'bic' => 'BIC',
            'account_holder' => 'Kontoinhaber',
            'consent' => 'SEPA-Einwilligung',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'iban' => strtoupper(preg_replace('/\s+/', '', (string) $this->input('iban'))),
            'bic' => strtoupper(preg_replace('/\s+/', '', (string) $this->input('bic'))) ?: null,
        ]);
    }
}
