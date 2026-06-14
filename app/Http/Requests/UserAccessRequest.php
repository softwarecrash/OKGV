<?php

namespace App\Http\Requests;

use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UserAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('updateAccess', $this->route('user'));
    }

    public function rules(): array
    {
        return [
            'role' => [
                'required',
                Rule::enum(UserRole::class)->only([
                    UserRole::Board,
                    UserRole::Treasurer,
                    UserRole::WaterManager,
                    UserRole::GardenManager,
                    UserRole::Tenant,
                ]),
            ],
            'permission_profile_id' => [
                'nullable',
                Rule::exists('permission_profiles', 'id')->where('is_active', true),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::enum(UserPermission::class)],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $subject = $this->route('user');

                if (! $subject instanceof User || ! $subject->isAdministrator()) {
                    return;
                }

                $validator->errors()->add(
                    'role',
                    'Administratorkonten können hier aus Sicherheitsgründen nicht herabgestuft werden.',
                );
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'permission_profile_id' => $this->input('permission_profile_id') ?: null,
            'permissions' => UserPermission::expandDependencies(
                $this->input('permissions', []),
            ),
        ]);
    }
}
