<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\WorkHourSubmissionStatus;
use App\Http\Requests\WorkHourSubmissionRequest;
use App\Http\Requests\WorkHourSubmissionReviewRequest;
use App\Models\Parcel;
use App\Models\WorkHourSubmission;
use App\Services\ActionIndicatorService;
use App\Services\WorkHourSubmissionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkHourSubmissionController extends Controller
{
    public function __construct(
        private readonly WorkHourSubmissionManager $manager,
        private readonly ActionIndicatorService $actionIndicatorService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', WorkHourSubmission::class);

        $submissions = WorkHourSubmission::query()
            ->with(['parcel', 'submitter.member', 'reviewer'])
            ->when(
                $request->user()->role === UserRole::Tenant,
                fn ($query) => $query->where('submitted_by', $request->user()->id),
            )
            ->orderByRaw("status = 'pending' desc")
            ->latest()
            ->paginate(20);
        $unresolvedRejectedIds = $request->user()->role === UserRole::Tenant
            ? WorkHourSubmission::query()
                ->unresolvedRejectedForUser($request->user()->id)
                ->pluck('id')
            : collect();

        $submissions->getCollection()->each(
            fn (WorkHourSubmission $submission) => $submission->setAttribute(
                'requires_tenant_action',
                $unresolvedRejectedIds->contains($submission->id),
            ),
        );

        return view('work-hour-submissions.index', [
            'submissions' => $submissions,
            'actionIndicators' => $this->actionIndicatorService->forUser($request->user()),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $member = $request->user()->member;

        if ($request->user()->role !== UserRole::Tenant || ! $member) {
            $route = $request->user()->canManageBilling()
                ? 'work-hours.index'
                : 'home';

            return redirect()->route($route)->withErrors([
                'work_hours' => 'Arbeitsstunden melden Pächter über ihr verknüpftes Mitgliedskonto. Für direkte Verwaltung nutze bitte die Arbeitsstundenkonten der Parzellen.',
            ]);
        }

        $this->authorize('create', WorkHourSubmission::class);
        $parcelIds = $member->parcelTenancies()
            ->activeOn()
            ->pluck('parcel_id');
        $parcels = Parcel::query()
            ->whereIn('id', $parcelIds)
            ->orderBy('parcel_number')
            ->get();
        $selectedParcelId = $request->integer('parcel_id');

        return view('work-hour-submissions.create', [
            'parcels' => $parcels,
            'selectedParcelId' => $parcels->contains('id', $selectedParcelId)
                ? $selectedParcelId
                : null,
        ]);
    }

    public function store(WorkHourSubmissionRequest $request): RedirectResponse
    {
        $this->manager->create(
            $request->safe()->only(['parcel_id', 'worked_at', 'hours', 'description']),
            $request->file('photo'),
            $request->user(),
        );

        return redirect()->route('work-hour-submissions.index')
            ->with('status', 'Arbeitsstunden wurden eingereicht und warten auf Prüfung.');
    }

    public function approve(
        WorkHourSubmissionReviewRequest $request,
        WorkHourSubmission $workHourSubmission,
    ): RedirectResponse {
        $this->manager->review(
            $workHourSubmission,
            $request->user(),
            WorkHourSubmissionStatus::Approved,
            $request->validated('review_note'),
        );

        return back()->with('status', 'Arbeitsstunden wurden bestätigt und übernommen.');
    }

    public function reject(
        WorkHourSubmissionReviewRequest $request,
        WorkHourSubmission $workHourSubmission,
    ): RedirectResponse {
        $this->manager->review(
            $workHourSubmission,
            $request->user(),
            WorkHourSubmissionStatus::Rejected,
            $request->validated('review_note'),
        );

        return back()->with('status', 'Arbeitsstundenmeldung wurde abgelehnt.');
    }

    public function photo(WorkHourSubmission $workHourSubmission): StreamedResponse
    {
        $this->authorize('downloadPhoto', $workHourSubmission);
        abort_unless(Storage::disk('local')->exists($workHourSubmission->photo_path), 404);

        return Storage::disk('local')->download(
            $workHourSubmission->photo_path,
            $workHourSubmission->photo_original_name,
            ['Content-Type' => $workHourSubmission->photo_mime],
        );
    }
}
