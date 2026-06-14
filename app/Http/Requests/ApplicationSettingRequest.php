<?php

namespace App\Http\Requests;

use App\Models\ApplicationSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplicationSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', ApplicationSetting::class);
    }

    public function rules(): array
    {
        return [
            'system_name' => ['required', 'string', 'max:80'],
            'default_board_permission_profile_id' => [
                'required',
                Rule::exists('permission_profiles', 'id')->where('is_active', true),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'system_name' => 'Systemname',
            'default_board_permission_profile_id' => 'Standardvorlage für Vorstände',
        ];
    }
}
