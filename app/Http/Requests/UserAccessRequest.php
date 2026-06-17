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
        $actor = $this->user();
        $subject = $this->route('user');

        if (! $actor instanceof User || ! $subject instanceof User || $actor->is($subject)) {
            return false;
        }

        if ($actor->isAdministrator()) {
            return true;
        }

        return $actor->role === UserRole::Board
            && ! $subject->isAdministrator()
            && in_array($subject->role, [UserRole::Board, UserRole::Tenant], true);
    }

    public function rules(): array
    {
        $assignableRoles = $this->user()?->isAdministrator()
            ? [
                UserRole::Board,
                UserRole::Treasurer,
                UserRole::WaterManager,
                UserRole::GardenManager,
                UserRole::Tenant,
            ]
            : [
                UserRole::Board,
                UserRole::Tenant,
            ];

        return [
            'role' => [
                'required',
                Rule::enum(UserRole::class)->only($assignableRoles),
            ],
            'permission_profile_id' => [
                'nullable',
                Rule::exists('permission_profiles', 'id')->where('is_active', true),
            ],
            'is_system_admin' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'string',
                Rule::in(array_column(UserPermission::availableCases(), 'value')),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $actor = $this->user();
                $subject = $this->route('user');

                if (! $subject instanceof User || ! $actor instanceof User) {
                    return;
                }

                if (
                    $subject->isAdministrator()
                    && ! $this->boolean('is_system_admin')
                    && User::query()->where('is_system_admin', true)->count() <= 1
                ) {
                    $validator->errors()->add(
                        'is_system_admin',
                        'Der letzte technische Administrator kann nicht entfernt werden.',
                    );
                }

                if ($actor->role === UserRole::Board && ! $actor->isAdministrator()) {
                    $requestedRole = UserRole::tryFrom((string) $this->input('role'));

                    if (! in_array($requestedRole, [UserRole::Board, UserRole::Tenant], true)) {
                        $validator->errors()->add(
                            'role',
                            'Vorstände können nur zwischen Pächter und Vorstand wechseln.',
                        );
                    }

                    if (! in_array($subject->role, [UserRole::Board, UserRole::Tenant], true)) {
                        $validator->errors()->add(
                            'role',
                            'Dieses Konto kann durch Vorstände nicht verändert werden.',
                        );
                    }

                    if ($subject->isAdministrator()) {
                        $validator->errors()->add(
                            'is_system_admin',
                            'Technische Administratorkonten können nur durch Administratoren verändert werden.',
                        );
                    }
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $permissions = $this->input('permissions', []);

        if (! $this->user()?->isAdministrator()) {
            $permissions = [];
        }

        $this->merge([
            'permission_profile_id' => $this->user()?->isAdministrator()
                ? $this->input('permission_profile_id') ?: null
                : null,
            'is_system_admin' => $this->user()?->isAdministrator()
                ? $this->boolean('is_system_admin')
                : false,
            'permissions' => UserPermission::expandDependencies(
                $permissions,
            ),
        ]);
    }
}
