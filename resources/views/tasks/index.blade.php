@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><h1 class="h2">Aufgaben und Wiedervorlagen</h1>@can('create', App\Models\Task::class)<a class="btn btn-primary" href="{{ route('tasks.create') }}">Aufgabe anlegen</a>@endcan</div>
    <p class="text-secondary">Hier stehen allgemeine Verwaltungsaufgaben und zugreifbare Beschlussaufgaben gemeinsam. Erledigte oder abgebrochene Aufgaben findest du über den Statusfilter.</p>
    <x-validation-errors />
    <form method="GET" class="card card-body mb-3">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label" for="q">Titel suchen</label><input class="form-control" id="q" name="q" maxlength="180" value="{{ request('q') }}"></div>
            <div class="col-md-6"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">Offen und in Bearbeitung</option>@foreach (App\Enums\TaskStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
        </div>
        <div class="d-flex flex-wrap gap-3 my-3">
            @foreach (['due' => 'Nur aktueller Handlungsbedarf für mich', 'mine' => 'Nur mir zugewiesen', 'archived' => 'Archiv einbeziehen'] as $field => $label)
                <div class="form-check"><input class="form-check-input" type="checkbox" id="{{ $field }}" name="{{ $field }}" value="1" @checked(request()->boolean($field))><label class="form-check-label" for="{{ $field }}">{{ $label }}</label></div>
            @endforeach
        </div>
        <p class="form-text">„Handlungsbedarf“ zeigt bearbeitbare offene Aufgaben ab Wiedervorlage oder Fälligkeit und hat Vorrang vor Status und Archiv.</p>
        <div><button class="btn btn-primary">Filtern</button> <a href="{{ route('tasks.index') }}">Filter zurücksetzen</a></div>
    </form>
    <div class="card card-body"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Aufgabe</th><th>Zuständig</th><th>Fällig</th><th>Wiedervorlage</th><th>Status</th></tr></thead><tbody>
        @forelse ($tasks as $task)
            <tr><td><a href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a>@if ($task->board_resolution_id)<span class="small text-secondary d-block">Beschlussaufgabe</span>@endif</td><td>{{ $task->assignee?->name ?? 'Noch nicht zugewiesen' }}</td><td>{{ $task->due_at->format('d.m.Y') }}</td><td>{{ $task->remind_at?->format('d.m.Y') ?? 'Am Fälligkeitstag' }}</td><td>{{ $task->status()->label() }}@if ($task->archived_at) · Archiviert @endif</td></tr>
        @empty
            <tr><td colspan="5">Keine Aufgaben in dieser Auswahl.</td></tr>
        @endforelse
    </tbody></table></div>{{ $tasks->links() }}</div>
</div>
@endsection
