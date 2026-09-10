<?php

namespace App\Http\Controllers;

use App\Enums\BoardResolutionResult;
use App\Enums\FeatureModule;
use App\Http\Requests\BoardActionRequest;
use App\Http\Requests\BoardAgendaItemRequest;
use App\Http\Requests\BoardFollowUpRequest;
use App\Http\Requests\BoardMeetingRequest;
use App\Http\Requests\BoardResolutionRequest;
use App\Models\BoardAgendaItem;
use App\Models\BoardFollowUp;
use App\Models\BoardMeeting;
use App\Models\BoardResolution;
use App\Models\Document;
use App\Models\User;
use App\Services\BoardMeetingManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BoardMeetingController extends Controller
{
    public function __construct(private readonly BoardMeetingManager $manager) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', BoardMeeting::class);
        $archived = $request->boolean('archived');
        $due = $request->boolean('due');
        $meetings = BoardMeeting::query()->when(! $archived, fn ($query) => $query->whereNull('archived_at'))
            ->orderByDesc('scheduled_at')->paginate(15, ['*'], 'meetings_page')->withQueryString();
        $tasks = BoardFollowUp::query()->with(['resolution.meeting', 'assignee'])
            ->whereNotNull('board_resolution_id')
            ->when($due, fn ($query) => $query->actionableFor($request->user()), fn ($query) => $query->open())
            ->orderBy('due_at')->orderBy('id')->paginate(15, ['*'], 'tasks_page')->withQueryString();

        return view('board-meetings.index', compact('meetings', 'tasks', 'archived', 'due'));
    }

    public function book(Request $request): View
    {
        $this->authorize('viewAny', BoardMeeting::class);
        $search = $request->validate(['q' => ['nullable', 'string', 'max:180'], 'result' => ['nullable', Rule::enum(BoardResolutionResult::class)]]);
        $resolutions = BoardResolution::query()->with(['meeting', 'agendaItem'])
            ->whereHas('meeting', fn ($query) => $query->whereNotNull('finalized_at'))
            ->when($search['q'] ?? null, fn ($query, $term) => $query->where(fn ($query) => $query->where('title', 'like', '%'.$term.'%')->orWhere('body', 'like', '%'.$term.'%')))
            ->when($search['result'] ?? null, fn ($query, $result) => $query->where('result', $result))
            ->orderByDesc('id')->paginate(20)->withQueryString();

        return view('board-meetings.book', compact('resolutions'));
    }

    public function create(): View
    {
        $this->authorize('create', BoardMeeting::class);

        return $this->form(new BoardMeeting(['scheduled_at' => now()]));
    }

    public function edit(BoardMeeting $meeting): View
    {
        $this->authorize('update', $meeting);

        return $this->form($meeting);
    }

    private function form(BoardMeeting $meeting): View
    {
        $documents = FeatureModule::Documents->enabled()
            ? Document::query()->orderBy('title')->get()->filter(fn (Document $document) => request()->user()->can('view', $document)) : collect();

        return view('board-meetings.form', compact('meeting', 'documents'));
    }

    public function store(BoardMeetingRequest $request): RedirectResponse
    {
        $meeting = $this->manager->save($request->validated(), $request->user());

        return redirect()->route('board-meetings.show', $meeting)->with('status', 'Sitzungsentwurf angelegt. Du kannst jetzt die Tagesordnung ergänzen.');
    }

    public function update(BoardMeetingRequest $request, BoardMeeting $meeting): RedirectResponse
    {
        $this->manager->save($request->validated(), $request->user(), $meeting);

        return redirect()->route('board-meetings.show', $meeting)->with('status', 'Sitzung und Protokoll gespeichert.');
    }

    public function show(BoardMeeting $meeting): View
    {
        $this->authorize('view', $meeting);
        $meeting->load(['agendaItems', 'resolutions.agendaItem', 'resolutions.followUps.assignee']);
        $documents = FeatureModule::Documents->enabled()
            ? $meeting->documents->filter(fn (Document $document) => request()->user()->can('view', $document)) : collect();

        return view('board-meetings.show', compact('meeting', 'documents'));
    }

    public function agendaForm(BoardMeeting $meeting, ?BoardAgendaItem $item = null): View
    {
        $this->authorize('update', $meeting);
        abort_if($item && $item->board_meeting_id !== $meeting->id, 404);

        return view('board-meetings.item-form', ['meeting' => $meeting, 'item' => $item ?? new BoardAgendaItem(['position' => ($meeting->agendaItems()->max('position') ?? 0) + 1]), 'kind' => 'agenda']);
    }

    public function saveAgenda(BoardAgendaItemRequest $request, BoardMeeting $meeting, ?BoardAgendaItem $item = null): RedirectResponse
    {
        $this->manager->saveAgenda($meeting, $request->validated(), $request->user(), $item);

        return redirect()->route('board-meetings.show', $meeting)->with('status', 'Tagesordnungspunkt gespeichert.');
    }

    public function resolutionForm(BoardMeeting $meeting, ?BoardResolution $resolution = null): View
    {
        $this->authorize('update', $meeting);
        abort_if($resolution && $resolution->board_meeting_id !== $meeting->id, 404);

        return view('board-meetings.item-form', ['meeting' => $meeting, 'item' => $resolution ?? new BoardResolution, 'kind' => 'resolution']);
    }

    public function saveResolution(BoardResolutionRequest $request, BoardMeeting $meeting, ?BoardResolution $resolution = null): RedirectResponse
    {
        $this->manager->saveResolution($meeting, $request->validated(), $request->user(), $resolution);

        return redirect()->route('board-meetings.show', $meeting)->with('status', 'Beschluss im Entwurf gespeichert.');
    }

    public function followUpForm(BoardResolution $resolution): View
    {
        $this->authorize('create', BoardMeeting::class);
        abort_unless($resolution->meeting->finalized_at && $resolution->result === BoardResolutionResult::Adopted, 403);
        $assignees = User::query()->orderBy('name')->get()->filter(fn (User $user) => $user->can('viewAny', BoardMeeting::class));

        return view('board-meetings.item-form', ['meeting' => $resolution->meeting, 'resolution' => $resolution, 'item' => new BoardFollowUp, 'kind' => 'task', 'assignees' => $assignees]);
    }

    public function addFollowUp(BoardFollowUpRequest $request, BoardResolution $resolution): RedirectResponse
    {
        $this->manager->addFollowUp($resolution, $request->validated(), $request->user());

        return redirect()->route('board-meetings.show', $resolution->board_meeting_id)->with('status', 'Beschlussaufgabe angelegt. Ab Fälligkeit erscheint ein Aktionshinweis.');
    }

    public function complete(BoardActionRequest $request, BoardFollowUp $task): RedirectResponse
    {
        $this->manager->complete($task, $request->user());

        return back()->with('status', 'Aufgabe erledigt. Der Aktionshinweis dafür ist entfernt.');
    }

    public function finalize(BoardActionRequest $request, BoardMeeting $meeting): RedirectResponse
    {
        $this->manager->finalize($meeting, $request->user());

        return redirect()->route('board-meetings.show', $meeting)->with('status', 'Protokoll unveränderbar abgeschlossen und als PDF gesichert.');
    }

    public function archive(BoardActionRequest $request, BoardMeeting $meeting): RedirectResponse
    {
        $this->manager->archive($meeting, $request->user());

        return redirect()->route('board-meetings.show', $meeting)->with('status', 'Sitzung archiviert. Beschlüsse und offene Aufgaben bleiben erhalten.');
    }

    public function pdf(BoardMeeting $meeting): StreamedResponse
    {
        $this->authorize('view', $meeting);
        abort_unless($meeting->finalized_at && $meeting->pdf_path && Storage::disk('local')->exists($meeting->pdf_path), 404);

        return Storage::disk('local')->download($meeting->pdf_path, 'vorstandsprotokoll-'.$meeting->id.'.pdf', ['Content-Type' => 'application/pdf']);
    }

    public function unarchive(BoardActionRequest $request, BoardMeeting $meeting): RedirectResponse
    {
        $this->manager->unarchive($meeting, $request->user());

        return redirect()->route('board-meetings.show', $meeting)->with('status', 'Sitzung aus dem Archiv zurückgeholt. Abgeschlossene Protokolle bleiben unveränderbar.');
    }

    public function document(BoardMeeting $meeting, Document $document): StreamedResponse
    {
        $this->authorize('view', $meeting);
        abort_unless($meeting->documents()->whereKey($document->id)->exists(), 404);
        $this->authorize('view', $document);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->original_name, ['X-Content-Type-Options' => 'nosniff']);
    }
}
