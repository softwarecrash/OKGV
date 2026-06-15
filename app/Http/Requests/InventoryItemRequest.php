<?php

namespace App\Http\Requests;

use App\Enums\InventoryItemStatus;
use App\Models\InventoryItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('inventory_item');

        return $item instanceof InventoryItem
            ? $this->user()->can('update', $item)
            : $this->user()->can('create', InventoryItem::class);
    }

    protected function prepareForValidation(): void
    {
        $price = str_replace(',', '.', trim((string) $this->input('purchase_price')));

        $this->merge([
            'inventory_number' => trim((string) $this->input('inventory_number')),
            'name' => trim((string) $this->input('name')),
            'category' => $this->nullableTrimmed('category'),
            'description' => $this->nullableTrimmed('description'),
            'location' => $this->nullableTrimmed('location'),
            'serial_number' => $this->nullableTrimmed('serial_number'),
            'notes' => $this->nullableTrimmed('notes'),
            'purchase_price' => $price === '' ? null : $price,
        ]);
    }

    public function rules(): array
    {
        $item = $this->route('inventory_item');

        return [
            'inventory_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('inventory_items')->ignore($item),
            ],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', Rule::enum(InventoryItemStatus::class), Rule::notIn([
                InventoryItemStatus::Issued->value,
            ])],
            'location' => ['nullable', 'string', 'max:255'],
            'purchased_at' => ['nullable', 'date'],
            'purchase_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'inventory_number' => 'Inventarnummer',
            'name' => 'Bezeichnung',
            'category' => 'Kategorie',
            'description' => 'Beschreibung',
            'status' => 'Status',
            'location' => 'Standort',
            'purchased_at' => 'Anschaffungsdatum',
            'purchase_price' => 'Anschaffungskosten',
            'serial_number' => 'Seriennummer',
            'notes' => 'interne Notizen',
        ];
    }

    private function nullableTrimmed(string $key): ?string
    {
        $value = trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }
}
