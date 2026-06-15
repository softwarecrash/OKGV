<?php

namespace App\Http\Requests;

use App\Enums\InventoryItemStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('return', $this->route('inventory_item'));
    }

    protected function prepareForValidation(): void
    {
        $value = trim((string) $this->input('condition_on_return'));
        $this->merge([
            'condition_on_return' => $value === '' ? null : $value,
            'issued_at' => $this->route('inventory_loan')?->issued_at?->toDateString(),
        ]);
    }

    public function rules(): array
    {
        return [
            'returned_at' => ['required', 'date', 'after_or_equal:issued_at'],
            'issued_at' => ['required', 'date'],
            'return_status' => [
                'required',
                Rule::in(array_map(
                    fn (InventoryItemStatus $status): string => $status->value,
                    InventoryItemStatus::returnStatuses(),
                )),
            ],
            'condition_on_return' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'returned_at' => 'Rückgabedatum',
            'return_status' => 'Status nach Rückgabe',
            'condition_on_return' => 'Zustand bei Rückgabe',
        ];
    }
}
