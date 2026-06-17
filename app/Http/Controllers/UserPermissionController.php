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
        $actor = $request->user();

        abort_unless($actor->can('viewAny', User::class), 403);

        $users = User::query()
            ->orderBy('name');

        if (! $actor->isAdministrator()) {
            $users
                ->whereIn('role', [
                    UserRole::Board->value,
                    UserRole::Tenant->value,
                ])
                ->whereKeyNot($actor->id);
        }

        return view('user-permissions.index', [
            'users' => $users->get(),
            'profiles' => PermissionProfile::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'permissions' => UserPermission::availableCases(),
            'defaultProfileId' => ApplicationSetting::current()
                ->default_board_permission_profile_id,
            'assignableRoles' => $actor->isAdministrator()
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
                ],
            'canManagePermissionDetails' => $actor->isAdministrator(),
            'administratorCount' => User::query()
                ->where('is_system_admin', true)
                ->count(),
        ]);
    }

    public function update(
        UserAccessRequest $request,
        User $user,
    ): RedirectResponse {
        $validated = $request->validated();
        $actor = $request->user();
        $targetRole = UserRole::from($validated['role']);
        $profile = isset($validated['permission_profile_id'])
            ? PermissionProfile::query()->findOrFail($validated['permission_profile_id'])
            : null;
        $permissions = $validated['permissions'] ?? [];

        if (! $actor->isAdministrator()) {
            $profile = $targetRole === UserRole::Board
                ? PermissionProfile::query()->find(ApplicationSetting::current()->default_board_permission_profile_id)
                : null;
            $permissions = $targetRole === UserRole::Board && $profile === null
                ? UserRole::Board->defaultPermissions()
                : [];
        }

        $this->manager->update(
            subject: $user,
            role: $targetRole,
            isSystemAdmin: (bool) ($validated['is_system_admin'] ?? false),
            permissions: $permissions,
            profile: $profile,
            actor: $actor,
        );

        return back()->with('status', 'Rolle und Zugriffsrechte wurden aktualisiert.');
    }
}
