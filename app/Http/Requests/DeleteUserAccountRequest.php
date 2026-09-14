<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class DeleteUserAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User && $this->user()?->can('delete', $user);
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'confirmation' => ['required', 'in:KONTO LÖSCHEN'],
        ];
    }
}
