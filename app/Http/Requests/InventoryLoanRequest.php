<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('issue', $this->route('inventory_item'));
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'borrower_name' => trim((string) $this->input('borrower_name')),
            'condition_on_issue' => $this->nullableTrimmed('condition_on_issue'),
            'notes' => $this->nullableTrimmed('notes'),
        ]);
    }

    public function rules(): array
    {
        return [
            'member_id' => [
                'nullable',
                'integer',
                Rule::exists('members', 'id')->whereNull('archived_at'),
            ],
            'borrower_name' => ['required', 'string', 'max:255'],
            'issued_at' => ['required', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'condition_on_issue' => ['nullable', 'string', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'member_id' => 'Mitglied',
            'borrower_name' => 'Empfänger',
            'issued_at' => 'Ausgabedatum',
            'due_at' => 'Rückgabefrist',
            'condition_on_issue' => 'Zustand bei Ausgabe',
            'notes' => 'Notizen',
        ];
    }

    private function nullableTrimmed(string $key): ?string
    {
        $value = trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }
}
