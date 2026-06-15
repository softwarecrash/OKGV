<?php

namespace App\Http\Requests;

use App\Enums\WaitingListStatus;
use App\Models\WaitingListEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WaitingListEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $entry = $this->route('waiting_list_entry');

        return $entry instanceof WaitingListEntry
            ? $this->user()->can('update', $entry)
            : $this->user()->can('create', WaitingListEntry::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => trim((string) $this->input('first_name')),
            'last_name' => trim((string) $this->input('last_name')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'phone' => $this->nullableTrimmed('phone'),
            'mobile' => $this->nullableTrimmed('mobile'),
            'notes' => $this->nullableTrimmed('notes'),
        ]);
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'applied_at' => ['required', 'date'],
            'priority' => ['required', 'integer', 'between:1,5'],
            'status' => ['required', Rule::enum(WaitingListStatus::class)],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'Vorname',
            'last_name' => 'Nachname',
            'email' => 'E-Mail-Adresse',
            'phone' => 'Telefonnummer',
            'mobile' => 'Mobilnummer',
            'applied_at' => 'Eingangsdatum',
            'priority' => 'Priorität',
            'status' => 'Status',
            'notes' => 'interne Notizen',
        ];
    }

    private function nullableTrimmed(string $key): ?string
    {
        $value = trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }
}
