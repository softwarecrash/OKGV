<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\UserMeterReadingPermissionRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserPermissionController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isAdministrator(), 403);

        return view('user-permissions.index', [
            'users' => User::query()
                ->whereIn('role', [UserRole::Administrator, UserRole::Board])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(
        UserMeterReadingPermissionRequest $request,
        User $user,
    ): RedirectResponse {
        abort_unless($request->user()->isAdministrator(), 403);
        abort_unless(
            in_array($user->role, [UserRole::Administrator, UserRole::Board], true),
            422,
        );

        $user->update($request->validated());

        AuditLogger::log(
            action: 'user.meter_reading_correction_permission.updated',
            actor: $request->user(),
            subject: $user,
            metadata: [
                'enabled' => $user->can_correct_meter_readings,
            ],
        );

        return back()->with('status', 'Korrekturrecht wurde aktualisiert.');
    }
}
