<?php

namespace App\Http\Controllers;

use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Http\Requests\UserAccessRequest;
use App\Models\ApplicationSetting;
use App\Models\PermissionProfile;
use App\Models\User;
use App\Services\UserAccessManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserPermissionController extends Controller
{
    public function __construct(private readonly UserAccessManager $manager) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->isAdministrator(), 403);

        return view('user-permissions.index', [
            'users' => User::query()
                ->orderBy('name')
                ->get(),
            'profiles' => PermissionProfile::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'permissions' => UserPermission::cases(),
            'defaultProfileId' => ApplicationSetting::current()
                ->default_board_permission_profile_id,
            'assignableRoles' => [
                UserRole::Board,
                UserRole::Treasurer,
                UserRole::WaterManager,
                UserRole::GardenManager,
                UserRole::Tenant,
            ],
        ]);
    }

    public function update(
        UserAccessRequest $request,
        User $user,
    ): RedirectResponse {
        $validated = $request->validated();
        $profile = isset($validated['permission_profile_id'])
            ? PermissionProfile::query()->findOrFail($validated['permission_profile_id'])
            : null;

        $this->manager->update(
            subject: $user,
            role: UserRole::from($validated['role']),
            permissions: $validated['permissions'] ?? [],
            profile: $profile,
            actor: $request->user(),
        );

        return back()->with('status', 'Rolle und Zugriffsrechte wurden aktualisiert.');
    }
}
