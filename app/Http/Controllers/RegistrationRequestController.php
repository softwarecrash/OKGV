<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrationApprovalRequest;
use App\Http\Requests\RegistrationMemberLinkRequest;
use App\Http\Requests\RegistrationRejectionRequest;
use App\Models\Member;
use App\Models\RegistrationRequest;
use App\Services\RegistrationCandidateMatcher;
use App\Services\RegistrationRequestManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegistrationRequestController extends Controller
{
    public function __construct(
        private readonly RegistrationRequestManager $manager,
        private readonly RegistrationCandidateMatcher $candidateMatcher,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', RegistrationRequest::class);

        return view('registration-requests.index', [
            'registrationRequests' => RegistrationRequest::query()
                ->with(['parcel', 'reviewer', 'user'])
                ->orderByRaw("status = 'pending' desc")
                ->latest()
                ->paginate(20),
        ]);
    }

    public function show(RegistrationRequest $registrationRequest): View
    {
        $this->authorize('view', $registrationRequest);
        $registrationRequest->loadMissing('user');
        $resolvedUser = $registrationRequest->resolvedUser();

        $memberQuery = Member::query()
            ->whereNull('user_id')
            ->when(
                $registrationRequest->parcel_id !== null,
                fn ($query) => $query->whereHas('parcelTenancies', fn ($query) => $query
                    ->activeOn()
                    ->where('parcel_id', $registrationRequest->parcel_id)),
            )
            ->orderBy('last_name')
            ->orderBy('first_name');

        $candidates = $this->candidateMatcher->rank($memberQuery->get(), $registrationRequest);
        $recommendedCandidate = $candidates->firstWhere('registration_recommended', true);

        return view('registration-requests.show', compact(
            'registrationRequest',
            'candidates',
            'recommendedCandidate',
            'resolvedUser',
        ));
    }

    public function approve(
        RegistrationApprovalRequest $request,
        RegistrationRequest $registrationRequest,
    ): RedirectResponse {
        $this->manager->approve(
            $registrationRequest,
            $request->filled('member_id')
                ? Member::query()->findOrFail($request->integer('member_id'))
                : null,
            $request->user(),
            $request->validated('review_note'),
            $request->validated('member_email_action'),
        );

        return redirect()->route('registration-requests.index')
            ->with('status', 'Pächterkonto wurde freigegeben und mit dem Mitglied verknüpft.');
    }

    public function reject(
        RegistrationRejectionRequest $request,
        RegistrationRequest $registrationRequest,
    ): RedirectResponse {
        $this->manager->reject(
            $registrationRequest,
            $request->user(),
            $request->validated('review_note'),
        );

        return redirect()->route('registration-requests.index')
            ->with('status', 'Registrierungsanfrage wurde abgelehnt.');
    }

    public function linkMember(
        RegistrationMemberLinkRequest $request,
        RegistrationRequest $registrationRequest,
    ): RedirectResponse {
        $this->manager->linkMember(
            $registrationRequest,
            Member::query()->findOrFail($request->integer('member_id')),
            $request->user(),
            $request->validated('review_note'),
            $request->validated('member_email_action'),
        );

        return redirect()->route('registration-requests.show', $registrationRequest)
            ->with('status', 'Benutzerkonto wurde nachträglich mit dem Mitglied verknüpft.');
    }

    public function linkAccount(RegistrationRequest $registrationRequest): RedirectResponse
    {
        $this->authorize('linkAccount', $registrationRequest);

        $this->manager->linkAccount($registrationRequest, request()->user());

        return redirect()->route('registration-requests.show', $registrationRequest)
            ->with('status', 'Registrierungsanfrage wurde mit dem vorhandenen Benutzerkonto verknüpft.');
    }
}
