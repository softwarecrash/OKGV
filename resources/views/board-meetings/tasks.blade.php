<div class="table-responsive">
    <table class="table align-middle"><thead><tr><th>Aufgabe</th><th>Zuständig</th><th>Fälligkeit</th><th>Status / Aktion</th></tr></thead><tbody>
        @forelse ($tasks as $task)
            <tr>
                <td>{{ $task->title }}<div class="small text-secondary">{!! nl2br(e($task->description)) !!}</div>@can('viewAny', App\Models\Task::class)<a class="small d-block" href="{{ route('tasks.show', $task->id) }}">Aufgabe / Wiedervorlage öffnen</a>@endcan @if ($showMeeting ?? false)<a class="small" href="{{ route('board-meetings.show', $task->resolution->board_meeting_id) }}#resolution-{{ $task->board_resolution_id }}">Beschluss #{{ $task->board_resolution_id }} · {{ $task->resolution->meeting->title }}</a>@endif</td>
                <td>{{ $task->assignee?->name ?? 'Vorstandsverwaltung' }}</td>
                <td>{{ $task->due_at->format('d.m.Y') }} @if ($task->isOpen() && $task->due_at->lte(today()))<span class="badge text-bg-warning">Fällig</span>@endif @if ($task->remind_at)<div class="small">Wiedervorlage: {{ $task->remind_at->format('d.m.Y') }}</div>@endif</td>
                <td><span>{{ $task->status()->label() }}</span>@if ($task->completed_at)<span> am {{ $task->completed_at->format('d.m.Y') }}</span>@endif @if ($task->archived_at)<span> · Archiviert</span>@endif @can('complete', $task)<form action="{{ route('board-follow-ups.complete', $task) }}" method="POST" class="mt-1">@csrf<button class="btn btn-sm btn-outline-success">Als erledigt markieren</button></form>@endcan @if ($task->isOpen() && $task->recurrence !== App\Enums\TaskRecurrence::None)<p class="small">{{ App\Enums\FeatureModule::Tasks->enabled() ? 'Beim Erledigen entsteht der nächste Termin.' : 'Wiederholung: Aufgabenmodul zum Abschließen aktivieren.' }}</p>@endif</td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-secondary">Keine Aufgaben in dieser Auswahl.</td></tr>
        @endforelse
    </tbody></table>
</div>
