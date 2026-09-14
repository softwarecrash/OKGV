<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdministrator() && $this->route('user') instanceof User;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }
}
