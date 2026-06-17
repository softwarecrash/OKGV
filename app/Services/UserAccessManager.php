<?php

namespace App\Services;

use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Models\PermissionProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UserAccessManager
{
    /**
     * @param  list<string>  $permissions
     */
    public function update(
        User $subject,
        UserRole $role,
        bool $isSystemAdmin,
        array $permissions,
        ?PermissionProfile $profile,
        User $actor,
    ): User {
        return DB::transaction(function () use ($subject, $role, $isSystemAdmin, $permissions, $profile, $actor): User {
            $subject = User::query()->lockForUpdate()->findOrFail($subject->id);
            $oldRole = $subject->role;
            $oldIsSystemAdmin = $subject->is_system_admin;
            $oldPermissions = $subject->permissions;

            $explicitPermissions = null;
            if ($role === UserRole::Board) {
                $unavailablePermissions = collect($oldPermissions ?? [])
                    ->filter(fn (string $permission): bool => ! UserPermission::from($permission)->isAvailable())
                    ->values()
                    ->all();
                $explicitPermissions = array_values(array_unique([
                    ...$profile?->permissions ?? $permissions,
                    ...$unavailablePermissions,
                ]));
            }

            $subject->update([
                'role' => $role,
                'is_system_admin' => $isSystemAdmin,
                'permissions' => $explicitPermissions,
                'permission_profile_id' => $role === UserRole::Board ? $profile?->id : null,
                'can_correct_meter_readings' => in_array(
                    UserPermission::CorrectMeterReadings->value,
                    $explicitPermissions ?? [],
                    true,
                ),
            ]);

            AuditLogger::log('user.access.updated', $actor, $subject, [
                'old_role' => $oldRole->value,
                'new_role' => $role->value,
                'old_is_system_admin' => $oldIsSystemAdmin,
                'new_is_system_admin' => $isSystemAdmin,
                'old_permissions' => $oldPermissions,
                'new_permissions' => $explicitPermissions,
                'permission_profile_id' => $profile?->id,
            ]);

            return $subject->refresh();
        });
    }
}
