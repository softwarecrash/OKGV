<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\WorkHourSubmissionStatus;
use App\Http\Requests\WorkHourSubmissionRequest;
use App\Http\Requests\WorkHourSubmissionReviewRequest;
use App\Models\Parcel;
use App\Models\WorkHourSubmission;
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
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', WorkHourSubmission::class);

        return view('work-hour-submissions.index', [
            'submissions' => WorkHourSubmission::query()
                ->with(['parcel', 'submitter.member', 'reviewer'])
                ->when(
                    $request->user()->role === UserRole::Tenant,
                    fn ($query) => $query->where('submitted_by', $request->user()->id),
                )
                ->orderByRaw("status = 'pending' desc")
                ->latest()
                ->paginate(20),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', WorkHourSubmission::class);
        $parcelIds = $request->user()->member->parcelTenancies()
            ->activeOn()
            ->pluck('parcel_id');

        return view('work-hour-submissions.create', [
            'parcels' => Parcel::query()
                ->whereIn('id', $parcelIds)
                ->orderBy('parcel_number')
                ->get(),
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
