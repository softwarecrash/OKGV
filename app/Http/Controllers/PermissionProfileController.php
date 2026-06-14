<?php

namespace App\Http\Controllers;

use App\Enums\UserPermission;
use App\Http\Requests\PermissionProfileRequest;
use App\Models\PermissionProfile;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PermissionProfileController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', PermissionProfile::class);

        return view('permission-profiles.index', [
            'profiles' => PermissionProfile::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', PermissionProfile::class);

        return view('permission-profiles.create', [
            'permissions' => UserPermission::cases(),
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
        $permissionProfile->update($request->validated());

        AuditLogger::log('permission_profile.updated', $request->user(), $permissionProfile, [
            'changed_fields' => array_keys($permissionProfile->getChanges()),
        ]);

        return redirect()
            ->route('permission-profiles.index')
            ->with('status', 'Rechtevorlage wurde aktualisiert.');
    }
}
