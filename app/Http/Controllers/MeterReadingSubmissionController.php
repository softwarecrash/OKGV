<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\MeterReadingSubmissionRequest;
use App\Http\Requests\MeterReadingSubmissionReviewRequest;
use App\Models\Meter;
use App\Models\MeterReadingSubmission;
use App\Services\MeterReadingSubmissionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MeterReadingSubmissionController extends Controller
{
    public function __construct(private readonly MeterReadingSubmissionManager $manager) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', MeterReadingSubmission::class);

        $submissions = MeterReadingSubmission::query()
            ->with(['meter.parcel', 'submitter.member', 'reviewer'])
            ->when(
                $request->user()->role === UserRole::Tenant,
                fn ($query) => $query->where('submitted_by', $request->user()->id),
            )
            ->orderByRaw("status = 'pending' desc")
            ->latest()
            ->paginate(20);

        return view('meter-reading-submissions.index', compact('submissions'));
    }

    public function create(Meter $meter): View
    {
        $this->authorize('submitReading', $meter);

        return view('meter-reading-submissions.create', compact('meter'));
    }

    public function store(
        MeterReadingSubmissionRequest $request,
        Meter $meter,
    ): RedirectResponse {
        $this->manager->create(
            $meter,
            $request->safe()->only(['reading_value', 'reading_date', 'notes']),
            $request->file('photo'),
            $request->user(),
        );

        return redirect()->route('meter-reading-submissions.index')
            ->with('status', 'Zählerstand wurde eingereicht und wartet auf Prüfung.');
    }

    public function approve(
        MeterReadingSubmissionReviewRequest $request,
        MeterReadingSubmission $meterReadingSubmission,
    ): RedirectResponse {
        $this->manager->approve(
            $meterReadingSubmission,
            $request->user(),
            $request->validated('review_note'),
        );

        return back()->with('status', 'Zählerstand wurde bestätigt und in die Historie übernommen.');
    }

    public function reject(
        MeterReadingSubmissionReviewRequest $request,
        MeterReadingSubmission $meterReadingSubmission,
    ): RedirectResponse {
        $this->manager->reject(
            $meterReadingSubmission,
            $request->user(),
            $request->validated('review_note'),
        );

        return back()->with('status', 'Zählerstandsmeldung wurde abgelehnt.');
    }

    public function photo(MeterReadingSubmission $meterReadingSubmission): StreamedResponse
    {
        $this->authorize('downloadPhoto', $meterReadingSubmission);
        abort_unless(
            Storage::disk('local')->exists($meterReadingSubmission->photo_path),
            404,
        );

        return Storage::disk('local')->download(
            $meterReadingSubmission->photo_path,
            $meterReadingSubmission->photo_original_name,
            ['Content-Type' => $meterReadingSubmission->photo_mime],
        );
    }
}
