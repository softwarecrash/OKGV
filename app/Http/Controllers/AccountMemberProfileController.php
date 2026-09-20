<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountMemberProfileRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccountMemberProfileController extends Controller
{
    public function edit(): View
    {
        return view('account.member-profile', [
            'member' => request()->user()->member()->firstOrFail(),
            'user' => request()->user(),
        ]);
    }

    public function update(AccountMemberProfileRequest $request): RedirectResponse
    {
        $member = $request->user()->member()->firstOrFail();
        $member->update($request->validated());

        AuditLogger::log('member.own_contact_details.updated', $request->user(), $member, [
            'changed_fields' => array_keys($member->getChanges()),
        ]);

        return redirect()
            ->route('account.member-profile.edit')
            ->with('status', 'Deine Kontaktdaten wurden gespeichert.');
    }
}
