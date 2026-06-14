<?php

namespace Database\Factories;

use App\Enums\UserPermission;
use App\Models\PermissionProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PermissionProfile>
 */
class PermissionProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'permissions' => [
                UserPermission::ViewAllMasterData->value,
                UserPermission::ReviewTenantRegistrations->value,
            ],
            'is_active' => true,
            'created_by' => null,
        ];
    }
}
