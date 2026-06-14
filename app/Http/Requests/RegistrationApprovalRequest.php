<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrationApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('registration_request'));
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'review_note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
