<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrationApprovalRequest;
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
                ->with(['parcel', 'reviewer'])
                ->orderByRaw("status = 'pending' desc")
                ->latest()
                ->paginate(20),
        ]);
    }

    public function show(RegistrationRequest $registrationRequest): View
    {
        $this->authorize('view', $registrationRequest);

        $candidates = $this->candidateMatcher->rank(Member::query()
            ->whereNull('user_id')
            ->whereHas('parcelTenancies', fn ($query) => $query
                ->activeOn()
                ->where('parcel_id', $registrationRequest->parcel_id))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(), $registrationRequest);
        $recommendedCandidate = $candidates->firstWhere('registration_recommended', true);

        return view('registration-requests.show', compact(
            'registrationRequest',
            'candidates',
            'recommendedCandidate',
        ));
    }

    public function approve(
        RegistrationApprovalRequest $request,
        RegistrationRequest $registrationRequest,
    ): RedirectResponse {
        $this->manager->approve(
            $registrationRequest,
            Member::query()->findOrFail($request->integer('member_id')),
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
}
