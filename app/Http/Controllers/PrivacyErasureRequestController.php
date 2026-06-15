<?php

namespace App\Http\Controllers;

use App\Enums\PrivacyErasureStatus;
use App\Http\Requests\PrivacyAnonymizeRequest;
use App\Http\Requests\PrivacyErasureReviewRequest;
use App\Models\Member;
use App\Models\PrivacyErasureRequest;
use App\Services\AuditLogger;
use App\Services\PrivacyErasureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use LogicException;

class PrivacyErasureRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $member = $this->requestedMember($request);

        abort_if(
            $member->privacyErasureRequests()
                ->whereIn('status', [
                    PrivacyErasureStatus::Pending->value,
                    PrivacyErasureStatus::Blocked->value,
                    PrivacyErasureStatus::Ready->value,
                ])
                ->exists(),
            422,
            'Für dieses Mitglied besteht bereits eine offene Löschanfrage.',
        );

        $erasureRequest = PrivacyErasureRequest::create([
            'member_id' => $member->id,
            'requested_by' => $request->user()->id,
            'status' => PrivacyErasureStatus::Pending,
            'requested_at' => now(),
        ]);

        AuditLogger::log('privacy.erasure_requested', $request->user(), $erasureRequest, [
            'member_id' => $member->id,
        ]);

        return redirect()->route('privacy.index')
            ->with('status', 'Die Löschanfrage wurde zur Prüfung eingereicht.');
    }

    public function review(
        PrivacyErasureReviewRequest $request,
        PrivacyErasureRequest $privacyErasureRequest,
        PrivacyErasureService $service,
    ): RedirectResponse {
        $service->review(
            $privacyErasureRequest,
            $request->user(),
            $request->validated('review_note'),
        );
        AuditLogger::log('privacy.erasure_reviewed', $request->user(), $privacyErasureRequest, [
            'blocker_count' => count($privacyErasureRequest->fresh()->blockers ?? []),
        ]);

        return redirect()->route('privacy.index')
            ->with('status', 'Die Löschanfrage wurde geprüft.');
    }

    public function anonymize(
        PrivacyAnonymizeRequest $request,
        PrivacyErasureRequest $privacyErasureRequest,
        PrivacyErasureService $service,
    ): RedirectResponse {
        try {
            $service->anonymize($privacyErasureRequest, $request->user());
        } catch (LogicException $exception) {
            return back()->withErrors(['confirmation' => $exception->getMessage()]);
        }

        AuditLogger::log('privacy.member_pseudonymized', $request->user(), $privacyErasureRequest);

        return redirect()->route('privacy.index')
            ->with('status', 'Die personenbezogenen Stammdaten wurden pseudonymisiert.');
    }

    private function requestedMember(Request $request): Member
    {
        if ($request->filled('member_id') && $request->user()->canManagePrivacy()) {
            $request->validate(['member_id' => ['required', 'integer', 'exists:members,id']]);

            return Member::query()->findOrFail($request->integer('member_id'));
        }

        abort_unless($request->user()->member()->exists(), 403);

        return $request->user()->member;
    }
}
