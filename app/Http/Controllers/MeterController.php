<?php

namespace App\Http\Controllers;

use App\Enums\MeterStatus;
use App\Enums\MeterType;
use App\Http\Requests\MeterRequest;
use App\Models\Meter;
use App\Models\Parcel;
use App\Services\AuditLogger;
use App\Services\MeterManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeterController extends Controller
{
    public function __construct(private readonly MeterManager $meterManager) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Meter::class);

        $meters = Meter::query()
            ->with('parcel')
            ->when(
                ! $request->user()->role->canViewAllMeters(),
                fn ($query) => $query->whereHas(
                    'parcel.tenancies.member',
                    fn ($query) => $query->where('user_id', $request->user()->id),
                ),
            )
            ->search($request->string('q')->trim()->toString())
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderByDesc('installed_at')
            ->paginate(20)
            ->withQueryString();

        return view('meters.index', [
            'meters' => $meters,
            'types' => MeterType::cases(),
            'statuses' => MeterStatus::cases(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Meter::class);

        return view('meters.create', [
            'meter' => new Meter([
                'parcel_id' => $request->integer('parcel_id') ?: null,
                'installed_at' => now(),
            ]),
            'parcels' => Parcel::query()->orderBy('parcel_number')->get(),
            'types' => MeterType::cases(),
        ]);
    }

    public function store(MeterRequest $request): RedirectResponse
    {
        $meter = $this->meterManager->create($request->validated());
        AuditLogger::log('meter.created', $request->user(), $meter);

        return redirect()->route('meters.show', $meter)->with('status', 'Zähler wurde angelegt.');
    }

    public function show(Meter $meter): View
    {
        $this->authorize('view', $meter);
        $meter->load([
            'parcel',
            'readings' => fn ($query) => $query
                ->with('corrections.corrector')
                ->latest('reading_date'),
        ]);

        return view('meters.show', compact('meter'));
    }

    public function edit(Meter $meter): View
    {
        $this->authorize('update', $meter);

        return view('meters.edit', compact('meter'));
    }

    public function update(MeterRequest $request, Meter $meter): RedirectResponse
    {
        $meter->update($request->validated());
        AuditLogger::log('meter.updated', $request->user(), $meter, [
            'changed_fields' => array_keys($meter->getChanges()),
        ]);

        return redirect()->route('meters.show', $meter)->with('status', 'Zähler wurde aktualisiert.');
    }
}
