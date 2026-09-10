<?php

namespace App\Http\Controllers;

use App\Enums\BoardResolutionResult;
use App\Enums\FeatureModule;
use App\Enums\TaskRecurrence;
use App\Enums\TaskStatus;
use App\Http\Requests\TaskActionRequest;
use App\Http\Requests\TaskRequest;
use App\Models\BoardMeeting;
use App\Models\BoardResolution;
use App\Models\Document;
use App\Models\Member;
use App\Models\Parcel;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskController extends Controller
{
    public function __construct(private readonly TaskManager $manager) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Task::class);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:180'], 'status' => ['nullable', Rule::enum(TaskStatus::class)],
            'due' => ['nullable', 'boolean'], 'mine' => ['nullable', 'boolean'], 'archived' => ['nullable', 'boolean'],
        ]);
        $query = Task::query()->visibleTo($request->user())->with('assignee');
        if ($request->boolean('due')) {
            $query->actionableFor($request->user());
        } else {
            $query->when(! $request->boolean('archived'), fn ($query) => $query->whereNull('archived_at'));
            match ($filters['status'] ?? 'active') {
                'open' => $query->open()->whereNull('started_at'),
                'in_progress' => $query->open()->whereNotNull('started_at'),
                'completed' => $query->whereNotNull('completed_at'),
                'cancelled' => $query->whereNotNull('cancelled_at'),
                default => $query->whereNull('completed_at')->whereNull('cancelled_at'),
            };
        }
        $query->when($request->boolean('mine'), fn ($query) => $query->where('assigned_to', $request->user()->id))
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where('title', 'like', '%'.$search.'%'));
        $tasks = $query->orderBy('due_at')->orderBy('id')->paginate(20)->withQueryString();

        return view('tasks.index', compact('tasks'));
    }

    public function create(): View
    {
        $this->authorize('create', Task::class);

        return $this->form(new Task(['due_at' => today(), 'recurrence' => TaskRecurrence::None]));
    }

    public function edit(Task $task): View
    {
        $this->authorize('update', $task);

        return $this->form($task);
    }

    private function form(Task $task): View
    {
        $actor = request()->user();
        $members = Member::query()->orderBy('last_name')->get()->filter(fn ($member) => $actor->can('view', $member));
        $parcels = Parcel::query()->orderBy('parcel_number')->get()->filter(fn ($parcel) => $actor->can('view', $parcel));
        $documents = FeatureModule::Documents->enabled() ? Document::query()->orderBy('title')->get()->filter(fn ($document) => $actor->can('view', $document)) : collect();
        $resolutions = $actor->can('viewAny', BoardMeeting::class)
            ? BoardResolution::query()->where('result', BoardResolutionResult::Adopted)->whereHas('meeting', fn ($query) => $query->whereNotNull('finalized_at'))->orderByDesc('id')->get() : collect();
        $assignees = User::query()->orderBy('name')->get()->filter(fn ($user) => $user->can('viewAny', Task::class));

        return view('tasks.form', compact('task', 'members', 'parcels', 'documents', 'resolutions', 'assignees'));
    }

    public function store(TaskRequest $request): RedirectResponse
    {
        $task = $this->manager->save($request->validated(), $request->user());

        return redirect()->route('tasks.show', $task)->with('status', 'Aufgabe angelegt.');
    }

    public function update(TaskRequest $request, Task $task): RedirectResponse
    {
        $this->manager->save($request->validated(), $request->user(), $task);

        return redirect()->route('tasks.show', $task)->with('status', 'Aufgabe aktualisiert.');
    }

    public function show(Task $task): View
    {
        $this->authorize('view', $task);
        $task->load(['assignee', 'member', 'parcel', 'document', 'resolution.meeting', 'successor']);
        $events = $task->events()->with('user')->paginate(25);

        return view('tasks.show', compact('task', 'events'));
    }

    public function action(TaskActionRequest $request, Task $task): RedirectResponse
    {
        $action = match (true) {
            $request->routeIs('tasks.start') => 'start', $request->routeIs('tasks.cancel') => 'cancel',
            $request->routeIs('tasks.archive') => 'archive', $request->routeIs('tasks.restore') => 'restore',
            default => 'complete',
        };
        if ($action === 'complete') {
            $this->manager->complete($task, $request->user());
        } else {
            $this->manager->act($task, $request->user(), $action, $request->validated('cancel_reason'));
        }
        $message = match ($action) {
            'start' => 'Aufgabe ist jetzt in Bearbeitung.', 'cancel' => 'Aufgabe mit Begründung abgebrochen.',
            'archive' => 'Aufgabe archiviert.', 'restore' => 'Aufgabe aus dem Archiv zurückgeholt.',
            default => $task->recurrence === TaskRecurrence::None ? 'Aufgabe erledigt. Der Hinweis wurde entfernt.' : 'Aufgabe erledigt. Der nächste Termin wurde angelegt und ist unten verlinkt.',
        };

        return redirect()->route('tasks.show', $task)->with('status', $message);
    }

    public function document(Task $task): StreamedResponse
    {
        $this->authorize('view', $task);
        abort_unless($task->document, 404);
        $this->authorize('view', $task->document);
        abort_unless(Storage::disk('local')->exists($task->document->file_path), 404);

        return Storage::disk('local')->download($task->document->file_path, $task->document->original_name, ['X-Content-Type-Options' => 'nosniff']);
    }
}
