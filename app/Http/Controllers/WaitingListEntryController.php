<?php

namespace App\Http\Controllers;

use App\Enums\WaitingListStatus;
use App\Http\Requests\WaitingListEntryRequest;
use App\Models\WaitingListEntry;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WaitingListEntryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', WaitingListEntry::class);

        $entries = WaitingListEntry::query()
            ->search($request->string('q')->trim()->toString())
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where(
                    'status',
                    $request->string('status')->toString(),
                ),
                fn ($query) => $query->whereIn(
                    'status',
                    WaitingListStatus::openValues(),
                ),
            )
            ->when(
                $request->filled('priority'),
                fn ($query) => $query->where(
                    'priority',
                    $request->integer('priority'),
                ),
            )
            ->orderBy('priority')
            ->orderBy('applied_at')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('waiting-list-entries.index', [
            'entries' => $entries,
            'statuses' => WaitingListStatus::cases(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', WaitingListEntry::class);

        return view('waiting-list-entries.create', [
            'entry' => new WaitingListEntry([
                'applied_at' => today(),
                'priority' => 3,
                'status' => WaitingListStatus::Waiting,
            ]),
            'statuses' => WaitingListStatus::cases(),
        ]);
    }

    public function store(WaitingListEntryRequest $request): RedirectResponse
    {
        $entry = WaitingListEntry::create($request->validated());
        AuditLogger::log('waiting_list.entry_created', $request->user(), $entry);

        return redirect()->route('waiting-list-entries.show', $entry)
            ->with('status', 'Wartelisteneintrag wurde angelegt.');
    }

    public function show(WaitingListEntry $waitingListEntry): View
    {
        $this->authorize('view', $waitingListEntry);

        return view('waiting-list-entries.show', [
            'entry' => $waitingListEntry,
        ]);
    }

    public function edit(WaitingListEntry $waitingListEntry): View
    {
        $this->authorize('update', $waitingListEntry);

        return view('waiting-list-entries.edit', [
            'entry' => $waitingListEntry,
            'statuses' => WaitingListStatus::cases(),
        ]);
    }

    public function update(
        WaitingListEntryRequest $request,
        WaitingListEntry $waitingListEntry,
    ): RedirectResponse {
        $before = $waitingListEntry->only([
            'applied_at',
            'priority',
            'status',
        ]);
        $waitingListEntry->update($request->validated());
        AuditLogger::log(
            'waiting_list.entry_updated',
            $request->user(),
            $waitingListEntry,
            [
                'before' => $before,
                'changed_fields' => array_keys($waitingListEntry->getChanges()),
            ],
        );

        return redirect()->route('waiting-list-entries.show', $waitingListEntry)
            ->with('status', 'Wartelisteneintrag wurde aktualisiert.');
    }
}
