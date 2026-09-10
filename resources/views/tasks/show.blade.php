@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><h1 class="h2">{{ $task->title }}</h1><div>@can('update', $task)<a class="btn btn-primary" href="{{ route('tasks.edit', $task) }}">Bearbeiten / Zuweisen</a>@endcan <a class="btn btn-outline-secondary" href="{{ route('tasks.index') }}">Aufgabenübersicht</a></div></div>
    <x-validation-errors />
    <section class="card card-body mb-3">
        <p><strong>{{ $task->status()->label() }}</strong>@if ($task->archived_at) · Archiviert @endif · Fällig: {{ $task->due_at->format('d.m.Y') }} · Zuständig: {{ $task->assignee?->name ?? 'Noch nicht zugewiesen' }}</p>
        <div>{!! nl2br(e($task->description)) !!}</div>
        <p class="mt-3">Wiedervorlage: {{ $task->remind_at?->format('d.m.Y') ?? 'Am Fälligkeitstag' }} · Wiederholung: {{ $task->recurrence->label() }}</p>
        @if ($task->cancelled_at)<p>Abgebrochen am {{ $task->cancelled_at->format('d.m.Y H:i') }}</p><p><strong>Begründung:</strong> {!! nl2br(e($task->cancel_reason)) !!}</p>@endif
        @if ($task->completed_at)<p>Erledigt am {{ $task->completed_at->format('d.m.Y H:i') }}</p>@endif
        @if ($task->successor) @can('view', $task->successor)<p><a href="{{ route('tasks.show', $task->successor) }}">Nächster Termin: {{ $task->successor->due_at->format('d.m.Y') }}</a></p>@endcan @endif
        <div class="d-flex flex-wrap gap-2">
            @foreach (['start' => 'Bearbeitung beginnen', 'complete' => 'Als erledigt markieren', 'archive' => 'Archivieren', 'restore' => 'Aus Archiv zurückholen'] as $ability => $label)
                @can($ability, $task)<form method="POST" action="{{ route('tasks.'.$ability, $task) }}">@csrf<button class="btn btn-outline-primary">{{ $label }}</button></form>@endcan
            @endforeach
        </div>
        @if ($task->isOpen() && $task->recurrence !== App\Enums\TaskRecurrence::None)<p class="form-text">Beim Erledigen wird der nächste Termin angelegt. Er kann bei verspäteter Erledigung bereits fällig sein.</p>@endif
    </section>
    <section class="card card-body mb-3"><h2 class="h5">Verknüpfte Datensätze</h2>
        @if ($task->member) @can('view', $task->member)<p><a href="{{ route('members.show', $task->member) }}">Mitglied: {{ $task->member->full_name }}</a></p>@else<p>Mitglied: Zugriff eingeschränkt.</p>@endcan @endif
        @if ($task->parcel) @can('view', $task->parcel)<p><a href="{{ route('parcels.show', $task->parcel) }}">Parzelle: {{ $task->parcel->parcel_number }}</a></p>@else<p>Parzelle: Zugriff eingeschränkt.</p>@endcan @endif
        @if ($task->document)
            @if (App\Enums\FeatureModule::Documents->enabled() && auth()->user()->can('view', $task->document))<p><a href="{{ route('tasks.document', $task) }}">Dokument: {{ $task->document->title }}</a></p>@else<p>Dokument: Zugriff eingeschränkt oder Modul deaktiviert.</p>@endif
        @endif
        @if ($task->resolution)<p><a href="{{ route('board-meetings.show', $task->resolution->meeting) }}#resolution-{{ $task->resolution->id }}">Beschluss #{{ $task->resolution->id }}: {{ $task->resolution->title }}</a></p>@endif
        @if (! $task->member_id && ! $task->parcel_id && ! $task->document_id && ! $task->board_resolution_id)<p class="text-secondary">Keine Verknüpfungen.</p>@endif
    </section>
    @can('cancel', $task)
        <details class="card card-body mb-3"><summary>Aufgabe abbrechen</summary><form method="POST" action="{{ route('tasks.cancel', $task) }}" class="mt-3">@csrf<p>Der Abbruch bleibt mit Begründung erhalten. Es wird kein Folgetermin angelegt.</p><label for="cancel_reason" class="form-label">Begründung</label><textarea class="form-control mb-3" id="cancel_reason" name="cancel_reason" maxlength="2000" required>{{ old('cancel_reason') }}</textarea><button class="btn btn-outline-danger">Begründet abbrechen</button></form></details>
    @endcan
    <section class="card card-body"><h2 class="h5">Verlauf</h2><p class="form-text">Bei älteren Beschlussaufgaben stehen frühere Vorgänge weiterhin im Auditlog. Hier beginnt der Verlauf mit Einführung der zentralen Aufgabenverwaltung.</p>
        <ul>@forelse ($events as $event)<li>{{ $event->created_at->format('d.m.Y H:i') }} · {{ $event->label() }} · {{ $event->user?->name ?? 'Ehemaliges Konto' }}</li>@empty<li>Noch keine neuen Verlaufsereignisse.</li>@endforelse</ul>{{ $events->links() }}
    </section>
</div>
@endsection
