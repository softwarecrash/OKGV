<?php

namespace App\Http\Controllers;

use App\Http\Requests\TenantTransitionRequest;
use App\Models\Document;
use App\Models\Member;
use App\Models\Parcel;
use App\Models\TenantTransition;
use App\Services\TenantTransitionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantTransitionController extends Controller
{
    public function __construct(
        private readonly TenantTransitionManager $manager,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', TenantTransition::class);

        return view('tenant-transitions.index', [
            'transitions' => TenantTransition::query()
                ->with(['parcel', 'completer'])
                ->latest('transfer_date')
                ->paginate(20),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', TenantTransition::class);

        $parcels = Parcel::query()
            ->whereHas('tenancies', fn ($query) => $query
                ->where('is_primary', true)
                ->activeOn())
            ->with([
                'tenancies' => fn ($query) => $query->activeOn()->with('member'),
                'meters' => fn ($query) => $query->with([
                    'readings' => fn ($query) => $query->with('corrections')->latest('reading_date'),
                ]),
            ])
            ->orderBy('parcel_number')
            ->get();
        $selectedParcelId = $request->integer('parcel_id');

        return view('tenant-transitions.create', [
            'parcels' => $parcels,
            'members' => Member::query()
                ->where('status', '!=', 'archived')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
            'selectedParcelId' => $parcels->contains('id', $selectedParcelId)
                ? $selectedParcelId
                : $parcels->first()?->id,
        ]);
    }

    public function store(TenantTransitionRequest $request): RedirectResponse
    {
        $transition = $this->manager->complete(
            $request->safe()->except(['photos', 'documents', 'confirm_open_claims']),
            $request->file('photos', []),
            $request->file('documents', []),
            $request->user(),
        );

        return redirect()->route('tenant-transitions.show', $transition)
            ->with('status', 'Pächterwechsel wurde vollständig durchgeführt und historisiert.');
    }

    public function show(TenantTransition $tenantTransition): View
    {
        $this->authorize('view', $tenantTransition);
        $tenantTransition->load([
            'parcel',
            'completer',
            'meterReadings.meter',
            'documents',
        ]);

        return view('tenant-transitions.show', [
            'transition' => $tenantTransition,
        ]);
    }

    public function document(
        TenantTransition $tenantTransition,
        Document $document,
    ): StreamedResponse {
        $this->authorize('view', $tenantTransition);
        abort_unless(
            $tenantTransition->documents()->whereKey($document->id)->exists(),
            404,
        );
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download(
            $document->file_path,
            $document->original_name,
            ['Content-Type' => $document->mime_type],
        );
    }
}
