<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteUserAccountRequest;
use App\Http\Requests\UpdateUserEmailRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserAccountController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        return view('user-accounts.index', [
            'users' => User::query()
                ->doesntHave('member')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function updateEmail(UpdateUserEmailRequest $request, User $user): RedirectResponse
    {
        $user = DB::transaction(function () use ($request, $user): User {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $user->update([
                'email' => $request->validated('email'),
                'email_verified_at' => null,
            ]);

            AuditLogger::log('user.email_updated', $request->user(), $user, [
                'email_changed' => true,
            ]);

            return $user;
        });

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'Die Login-E-Mail-Adresse wurde geändert. Die neue Adresse muss jetzt bestätigt werden.');
    }

    public function destroy(DeleteUserAccountRequest $request, User $user): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $user): void {
                $user = User::query()->lockForUpdate()->findOrFail($user->id);
                $this->authorize('delete', $user);
                $memberId = $user->member()->value('id');

                AuditLogger::log('user.deleted', $request->user(), null, [
                    'deleted_user_id' => $user->id,
                    'member_id' => $memberId,
                ]);

                $user->delete();
            });
        } catch (QueryException) {
            return back()->withErrors([
                'account' => 'Dieses Konto besitzt bereits fachliche Historie und kann nicht entfernt werden. Nutze stattdessen die DSGVO-Löschprüfung.',
            ]);
        }

        return redirect()->route('members.index')
            ->with('status', 'Das Benutzerkonto wurde entfernt. Der Mitgliedsstammsatz und seine Vereinsdaten bleiben erhalten.');
    }
}
