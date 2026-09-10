<?php

namespace App\Http\Controllers;

use App\Http\Requests\GardenInspectionFindingRequest;
use App\Http\Requests\GardenInspectionRequest;
use App\Models\GardenInspection;
use App\Models\GardenInspectionFinding;
use App\Models\Member;
use App\Models\Parcel;
use App\Models\Task;
use App\Services\GardenInspectionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GardenInspectionController extends Controller
{
    public function __construct(private readonly GardenInspectionManager $manager) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', GardenInspection::class);
        $rows = GardenInspection::query()->when(! $request->user()->can('create', GardenInspection::class), fn ($q) => $q->whereHas('findings.parcel', fn ($q) => $q->whereHas('tenancies', fn ($q) => $q->activeOn()->whereHas('member', fn ($q) => $q->where('user_id', $request->user()->id)))))->latest('inspected_at')->paginate();

        return view('garden-inspections.index', compact('rows'));
    }

    public function create(): View
    {
        $this->authorize('create', GardenInspection::class);

        return view('garden-inspections.form');
    }

    public function store(GardenInspectionRequest $request): RedirectResponse
    {
        $row = $this->manager->create($request->validated(), $request->user());

        return redirect()->route('garden-inspections.show', $row)->with('status', 'Begehung angelegt.');
    }

    public function show(Request $request, GardenInspection $inspection): View
    {
        $this->authorize('view', $inspection);
        $findings = $inspection->findings()->with(['parcel', 'responsibleMember', 'task'])->when(! $request->user()->can('create', GardenInspection::class), fn ($q) => $q->whereHas('parcel', fn ($q) => $q->whereHas('tenancies', fn ($q) => $q->activeOn()->whereHas('member', fn ($q) => $q->where('user_id', $request->user()->id)))))->get();

        return view('garden-inspections.show', ['inspection' => $inspection, 'findings' => $findings, 'parcels' => Parcel::query()->orderBy('parcel_number')->get(), 'members' => Member::query()->orderBy('last_name')->get(), 'tasks' => Task::query()->visibleTo($request->user())->open()->orderBy('title')->get()]);
    }

    public function finding(GardenInspectionFindingRequest $request, GardenInspection $inspection): RedirectResponse
    {
        $this->manager->finding($inspection, $request->validated(), $request->file('photo'), $request->user());

        return back()->with('status', 'Feststellung gespeichert.');
    }

    public function resolve(Request $request, GardenInspectionFinding $finding): RedirectResponse
    {
        $this->authorize('resolve', $finding);
        $this->manager->resolve($finding, $request->user());

        return back()->with('status', 'Nachkontrolle als erledigt protokolliert.');
    }

    public function photo(Request $request, GardenInspectionFinding $finding): StreamedResponse
    {
        $this->authorize('viewFinding', $finding);
        abort_unless($finding->photo_path && Storage::disk('local')->exists($finding->photo_path), 404);

        return Storage::disk('local')->download($finding->photo_path, $finding->photo_original_name, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function finalize(Request $request, GardenInspection $inspection): RedirectResponse
    {
        $this->manager->finalize($inspection, $request->user());

        return back()->with('status', 'Begehung abgeschlossen. Das PDF-Protokoll kann jetzt abgerufen werden.');
    }

    public function pdf(Request $request, GardenInspection $inspection): StreamedResponse
    {
        $this->authorize('view', $inspection);
        abort_unless($inspection->pdf_path && Storage::disk('local')->exists($inspection->pdf_path), 404);

        return Storage::disk('local')->download($inspection->pdf_path, 'gartenbegehung-'.$inspection->id.'.pdf');
    }
}
