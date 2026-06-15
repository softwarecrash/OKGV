<?php

namespace App\Http\Requests;

use App\Enums\UserPermission;
use App\Models\PermissionProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $profile = $this->route('permission_profile');

        return $profile instanceof PermissionProfile
            ? $this->user()->can('update', $profile)
            : $this->user()->can('create', PermissionProfile::class);
    }

    public function rules(): array
    {
        $profile = $this->route('permission_profile');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('permission_profiles')->ignore($profile),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'string',
                Rule::in(array_column(UserPermission::availableCases(), 'value')),
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'permissions' => UserPermission::expandDependencies(
                $this->input('permissions', []),
            ),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
