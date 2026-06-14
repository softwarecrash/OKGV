<?php

namespace App\Http\Requests;

use App\Enums\SepaMandateStatus;
use App\Enums\SepaMandateType;
use App\Models\SepaMandate;
use App\Rules\ValidIban;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SepaMandateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $mandate = $this->route('sepa_mandate');

        return $mandate instanceof SepaMandate
            ? $this->user()->can('update', $mandate)
            : $this->user()->can('create', SepaMandate::class);
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'mandate_reference' => [
                'required',
                'string',
                'max:35',
                'regex:/^[A-Z0-9+?\\/\\-:().,\']+$/',
                Rule::unique('sepa_mandates', 'mandate_reference')
                    ->ignore($this->route('sepa_mandate')),
            ],
            'iban' => ['required', 'string', 'max:42', new ValidIban],
            'bic' => ['nullable', 'string', 'regex:/^[A-Z0-9]{8}([A-Z0-9]{3})?$/'],
            'account_holder' => ['required', 'string', 'max:70'],
            'signed_at' => ['required', 'date'],
            'valid_from' => ['required', 'date', 'after_or_equal:signed_at'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'mandate_type' => ['required', Rule::enum(SepaMandateType::class)],
            'status' => ['required', Rule::enum(SepaMandateStatus::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mandate_reference' => strtoupper(preg_replace(
                '/\s+/',
                '-',
                trim((string) $this->input('mandate_reference')),
            )),
            'iban' => strtoupper(preg_replace('/\s+/', '', (string) $this->input('iban'))),
            'bic' => strtoupper(preg_replace('/\s+/', '', (string) $this->input('bic'))) ?: null,
        ]);
    }
}
