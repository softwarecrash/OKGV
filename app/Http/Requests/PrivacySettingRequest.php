<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrivacySettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->member()->exists();
    }

    protected function prepareForValidation(): void
    {
        foreach (['share_name', 'share_email', 'share_phone', 'share_mobile', 'share_address'] as $field) {
            $this->merge([$field => $this->boolean($field)]);
        }
    }

    public function rules(): array
    {
        return [
            'share_name' => ['required', 'boolean'],
            'share_email' => ['required', 'boolean'],
            'share_phone' => ['required', 'boolean'],
            'share_mobile' => ['required', 'boolean'],
            'share_address' => ['required', 'boolean'],
        ];
    }
}
