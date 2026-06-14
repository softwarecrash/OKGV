<?php

namespace App\Http\Requests;

use App\Models\SepaSetting;
use App\Rules\ValidCreditorIdentifier;
use App\Rules\ValidIban;
use Illuminate\Foundation\Http\FormRequest;

class SepaSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', SepaSetting::class);
    }

    public function rules(): array
    {
        return [
            'creditor_name' => ['required', 'string', 'max:70'],
            'creditor_identifier' => ['required', 'string', 'max:35', new ValidCreditorIdentifier],
            'iban' => ['required', 'string', 'max:42', new ValidIban],
            'bic' => ['nullable', 'string', 'regex:/^[A-Z0-9]{8}([A-Z0-9]{3})?$/'],
            'batch_booking' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'creditor_identifier' => strtoupper(preg_replace('/\s+/', '', (string) $this->input('creditor_identifier'))),
            'iban' => strtoupper(preg_replace('/\s+/', '', (string) $this->input('iban'))),
            'bic' => strtoupper(preg_replace('/\s+/', '', (string) $this->input('bic'))) ?: null,
            'batch_booking' => $this->boolean('batch_booking'),
        ]);
    }
}
