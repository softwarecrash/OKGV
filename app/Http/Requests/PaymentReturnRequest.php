<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('return', $this->route('payment_batch_item'));
    }

    public function rules(): array
    {
        return [
            'return_reason_code' => ['required', 'string', 'size:4', 'regex:/^[A-Z0-9]{4}$/'],
            'return_reason_text' => ['nullable', 'string', 'max:255'],
            'returned_at' => ['required', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'return_reason_code' => strtoupper(trim((string) $this->input('return_reason_code'))),
        ]);
    }
}
