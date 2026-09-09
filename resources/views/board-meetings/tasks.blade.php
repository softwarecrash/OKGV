<div class="table-responsive">
    <table class="table align-middle"><thead><tr><th>Aufgabe</th><th>Zuständig</th><th>Fälligkeit</th><th>Status / Aktion</th></tr></thead><tbody>
        @forelse ($tasks as $task)
            <tr>
                <td>{{ $task->title }}<div class="small text-secondary">{!! nl2br(e($task->description)) !!}</div>@if ($showMeeting ?? false)<a class="small" href="{{ route('board-meetings.show', $task->resolution->board_meeting_id) }}#resolution-{{ $task->board_resolution_id }}">Beschluss #{{ $task->board_resolution_id }} · {{ $task->resolution->meeting->title }}</a>@endif</td>
                <td>{{ $task->assignee?->name ?? 'Vorstandsverwaltung' }}</td>
                <td>{{ $task->due_at->format('d.m.Y') }} @if (! $task->completed_at && $task->due_at->lte(today()))<span class="badge text-bg-warning">Fällig</span>@endif</td>
                <td>@if ($task->completed_at)<span>Erledigt am {{ $task->completed_at->format('d.m.Y') }}</span>@else<span>Offen</span>@can('complete', $task)<form action="{{ route('board-follow-ups.complete', $task) }}" method="POST" class="mt-1">@csrf<button class="btn btn-sm btn-outline-success">Als erledigt markieren</button></form>@endcan @endif</td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-secondary">Keine Aufgaben in dieser Auswahl.</td></tr>
        @endforelse
    </tbody></table>
</div>
