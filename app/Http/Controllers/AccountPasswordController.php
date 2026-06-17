<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountPasswordRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AccountPasswordController extends Controller
{
    public function edit(): View
    {
        return view('account.password');
    }

    public function update(AccountPasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->forceFill([
            'password' => Hash::make($request->validated('password')),
        ])->save();

        AuditLogger::log('user.password.updated', $user, $user);

        return redirect()
            ->route('account.password.edit')
            ->with('status', 'Dein Passwort wurde geändert.');
    }
}
