<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UserMeterReadingPermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdministrator();
    }

    public function rules(): array
    {
        return [
            'can_correct_meter_readings' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $subject = $this->route('user');

                if (! $subject instanceof User
                    || in_array($subject->role, [UserRole::Administrator, UserRole::Board], true)) {
                    return;
                }

                $validator->errors()->add(
                    'can_correct_meter_readings',
                    'Das Korrekturrecht darf nur Administratoren und Vorständen zugewiesen werden.',
                );
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'can_correct_meter_readings' => $this->boolean('can_correct_meter_readings'),
        ]);
    }
}
