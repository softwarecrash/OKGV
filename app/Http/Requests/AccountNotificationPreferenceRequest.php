<?php

namespace App\Http\Requests;

use App\Enums\EmailNotificationTopic;
use Illuminate\Foundation\Http\FormRequest;

class AccountNotificationPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'preferences' => ['required', 'array:'.implode(',', array_column(EmailNotificationTopic::cases(), 'value'))],
            'preferences.*' => ['boolean'],
        ];
    }

    public function preferenceValues(): array
    {
        return collect($this->validated('preferences', []))
            ->mapWithKeys(fn ($value, string $key): array => [$key => $this->boolean("preferences.{$key}")])
            ->all();
    }
}
