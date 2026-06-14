<?php

namespace App\Http\Requests;

use App\Models\CommunicationSetting;
use Illuminate\Foundation\Http\FormRequest;

class SmtpTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('test', CommunicationSetting::class);
    }

    public function rules(): array
    {
        return [
            'test_email' => ['required', 'email', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'test_email' => 'Zieladresse',
        ];
    }
}
