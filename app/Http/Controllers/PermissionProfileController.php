<?php

namespace App\Http\Controllers;

use App\Enums\UserPermission;
use App\Http\Requests\PermissionProfileRequest;
use App\Models\ApplicationSetting;
use App\Models\PermissionProfile;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PermissionProfileController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', PermissionProfile::class);

        return view('permission-profiles.index', [
            'profiles' => PermissionProfile::query()->withCount('users')->orderBy('name')->get(),
            'defaultProfileIds' => ApplicationSetting::query()
                ->whereNotNull('default_board_permission_profile_id')
                ->pluck('default_board_permission_profile_id')
                ->all(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', PermissionProfile::class);

        return view('permission-profiles.create', [
            'permissions' => UserPermission::availableCases(),
        ]);
    }

    public function store(PermissionProfileRequest $request): RedirectResponse
    {
        $profile = PermissionProfile::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        AuditLogger::log('permission_profile.created', $request->user(), $profile);

        return redirect()
            ->route('permission-profiles.index')
            ->with('status', 'Rechtevorlage wurde angelegt.');
    }

    public function edit(PermissionProfile $permissionProfile): View
    {
        $this->authorize('update', $permissionProfile);

        return view('permission-profiles.edit', [
            'profile' => $permissionProfile,
            'permissions' => UserPermission::cases(),
        ]);
    }

    public function update(
        PermissionProfileRequest $request,
        PermissionProfile $permissionProfile,
    ): RedirectResponse {
        $data = $request->validated();
        $unavailablePermissions = collect($permissionProfile->permissions)
            ->filter(fn (string $permission): bool => ! UserPermission::from($permission)->isAvailable())
            ->values()
            ->all();
        $data['permissions'] = array_values(array_unique([
            ...$data['permissions'] ?? [],
            ...$unavailablePermissions,
        ]));
        $permissionProfile->update($data);

        AuditLogger::log('permission_profile.updated', $request->user(), $permissionProfile, [
            'changed_fields' => array_keys($permissionProfile->getChanges()),
        ]);

        return redirect()
            ->route('permission-profiles.index')
            ->with('status', 'Rechtevorlage wurde aktualisiert.');
    }

    public function destroy(Request $request, PermissionProfile $permissionProfile): RedirectResponse
    {
        $this->authorize('delete', $permissionProfile);

        DB::transaction(function () use ($request, $permissionProfile): void {
            $profile = PermissionProfile::query()->lockForUpdate()->findOrFail($permissionProfile->id);

            if ($profile->isBuiltIn()) {
                throw ValidationException::withMessages([
                    'profile' => 'Die feste Standardvorlage kann nicht gelöscht werden.',
                ]);
            }

            $isAssigned = User::query()
                ->where('permission_profile_id', $profile->id)
                ->lockForUpdate()
                ->exists();
            $isDefault = ApplicationSetting::query()
                ->where('default_board_permission_profile_id', $profile->id)
                ->lockForUpdate()
                ->exists();

            if ($isAssigned || $isDefault) {
                throw ValidationException::withMessages([
                    'profile' => 'Die Vorlage wird noch verwendet und kann deshalb nicht gelöscht werden.',
                ]);
            }

            AuditLogger::log('permission_profile.deleted', $request->user(), $profile, [
                'name' => $profile->name,
            ]);
            $profile->delete();
        });

        return redirect()
            ->route('permission-profiles.index')
            ->with('status', 'Rechtevorlage wurde gelöscht.');
    }
}
