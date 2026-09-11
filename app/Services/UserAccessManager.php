<?php

namespace App\Services;

use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Models\PermissionProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

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
            // Serialize access changes so concurrent demotions cannot remove all administrators.
            $administrators = User::query()
                ->where(fn ($query) => $query->where('is_system_admin', true)->orWhere('role', UserRole::Administrator))
                ->orderBy('id')->lockForUpdate()->get();
            $actor = User::query()->lockForUpdate()->findOrFail($actor->id);
            $subject = User::query()->lockForUpdate()->findOrFail($subject->id);
            Gate::forUser($actor)->authorize('updateAccess', $subject);

            if (! $actor->isAdministrator() && ($isSystemAdmin || ! in_array($role, [UserRole::Board, UserRole::Tenant], true))) {
                throw ValidationException::withMessages(['role' => 'Vorstände können nur zwischen Pächter und Vorstand wechseln.']);
            }

            if ($subject->isAdministrator() && ! $isSystemAdmin && $role !== UserRole::Administrator && $administrators->count() <= 1) {
                throw ValidationException::withMessages(['is_system_admin' => 'Der letzte technische Administrator kann nicht entfernt werden.']);
            }

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
                'permission_profile_id' => in_array($role, [UserRole::Board, UserRole::Tenant], true)
                    ? $profile?->id
                    : null,
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
