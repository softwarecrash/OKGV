<?php

namespace App\Http\Controllers;

use App\Enums\ParcelStatus;
use App\Http\Requests\ParcelRequest;
use App\Models\Parcel;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParcelController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Parcel::class);

        $parcels = Parcel::query()
            ->when(
                ! $request->user()->canViewAllMasterData(),
                fn ($query) => $query->whereHas(
                    'tenancies',
                    fn ($query) => $query
                        ->activeOn()
                        ->whereHas('member', fn ($query) => $query
                            ->where('user_id', $request->user()->id)),
                ),
            )
            ->search($request->string('q')->trim()->toString())
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString()),
            )
            ->orderBy('parcel_number')
            ->paginate(20)
            ->withQueryString();

        return view('parcels.index', [
            'parcels' => $parcels,
            'statuses' => ParcelStatus::cases(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Parcel::class);

        return view('parcels.create', [
            'parcel' => new Parcel,
            'statuses' => ParcelStatus::cases(),
        ]);
    }

    public function store(ParcelRequest $request): RedirectResponse
    {
        $parcel = Parcel::create($request->validated());
        AuditLogger::log('parcel.created', $request->user(), $parcel);

        return redirect()->route('parcels.show', $parcel)
            ->with('status', 'Parzelle wurde angelegt.');
    }

    public function show(Parcel $parcel): View
    {
        $this->authorize('view', $parcel);

        $parcel->load([
            'tenancies' => fn ($query) => $query
                ->when(
                    ! request()->user()->canViewAllMasterData(),
                    fn ($query) => $query->whereHas(
                        'member',
                        fn ($query) => $query->where('user_id', request()->user()->id),
                    ),
                )
                ->with('member')
                ->latest('starts_at'),
        ]);

        return view('parcels.show', [
            'parcel' => $parcel,
        ]);
    }

    public function edit(Parcel $parcel): View
    {
        $this->authorize('update', $parcel);

        return view('parcels.edit', [
            'parcel' => $parcel,
            'statuses' => ParcelStatus::cases(),
        ]);
    }

    public function update(ParcelRequest $request, Parcel $parcel): RedirectResponse
    {
        $parcel->update($request->validated());
        AuditLogger::log('parcel.updated', $request->user(), $parcel, [
            'changed_fields' => array_keys($parcel->getChanges()),
        ]);

        return redirect()->route('parcels.show', $parcel)
            ->with('status', 'Parzelle wurde aktualisiert.');
    }
}
