<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AccountMemberProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->member()->exists() ?? false;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'street' => ['required', 'string', 'max:255'],
            'zip' => ['required', 'string', 'max:10'],
            'city' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }
}
