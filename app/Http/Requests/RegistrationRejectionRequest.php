<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrationRejectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('registration_request'));
    }

    public function rules(): array
    {
        return [
            'review_note' => ['required', 'string', 'max:255'],
        ];
    }
}
