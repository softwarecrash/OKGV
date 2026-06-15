<?php

namespace App\Http\Requests;

use App\Models\PrivacyErasureRequest;
use Illuminate\Foundation\Http\FormRequest;

class PrivacyAnonymizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $erasureRequest = $this->route('privacy_erasure_request');

        return $erasureRequest instanceof PrivacyErasureRequest
            && $this->user()->can('anonymize', $erasureRequest);
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'confirmation' => ['required', 'in:PSEUDONYMISIEREN'],
        ];
    }

    public function attributes(): array
    {
        return [
            'current_password' => 'aktuelles Passwort',
            'confirmation' => 'Sicherheitsbestätigung',
        ];
    }
}
