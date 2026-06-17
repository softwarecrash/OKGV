<?php

namespace App\Http\Requests;

use App\Models\CommunicationSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommunicationSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', CommunicationSetting::class);
    }

    public function rules(): array
    {
        return [
            'smtp_enabled' => ['required', 'boolean'],
            'mailer_transport' => ['required', Rule::in(['smtp', 'sendmail'])],
            'smtp_scheme' => ['required', Rule::in(['smtp', 'smtps', 'none'])],
            'smtp_host' => ['required_if:mailer_transport,smtp', 'nullable', 'string', 'max:255'],
            'smtp_port' => ['required_if:mailer_transport,smtp', 'nullable', 'integer', 'between:1,65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'sendmail_path' => ['nullable', 'string', 'max:255'],
            'clear_credentials' => ['required', 'boolean'],
            'from_address' => ['required', 'email', 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'smtp_enabled' => $this->boolean('smtp_enabled'),
            'clear_credentials' => $this->boolean('clear_credentials'),
        ]);
    }
}
