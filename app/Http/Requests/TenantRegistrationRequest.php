<?php

namespace App\Http\Requests;

use App\Enums\RegistrationRequestStatus;
use App\Models\RegistrationRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class TenantRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (RegistrationRequest::query()
                        ->where('email', $value)
                        ->where('status', RegistrationRequestStatus::Pending)
                        ->exists()) {
                        $fail('Für diese E-Mail-Adresse wird bereits eine Registrierung geprüft.');
                    }
                },
            ],
            'parcel_number' => ['nullable', 'string', 'max:255', 'exists:parcels,parcel_number'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => trim((string) $this->input('first_name')),
            'last_name' => trim((string) $this->input('last_name')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'parcel_number' => trim((string) $this->input('parcel_number')) ?: null,
        ]);
    }
}
