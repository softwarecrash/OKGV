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
            'default_work_hours_required' => [
                'required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999.99',
            ],
            'default_work_hour_penalty_rate' => [
                'required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'system_name' => 'Systemname',
            'default_board_permission_profile_id' => 'Standardvorlage für Vorstände',
            'default_work_hours_required' => 'Pflichtstunden je Parzelle',
            'default_work_hour_penalty_rate' => 'Betrag je Fehlstunde',
        ];
    }
}
