@extends('layouts.app')

@section('content')
@php
    $heading = match ($kind) { 'agenda' => 'Tagesordnungspunkt', 'resolution' => 'Beschluss', default => 'Aufgabe / Wiedervorlage' };
    $action = match ($kind) {
        'agenda' => $item->exists ? route('board-agenda.update', [$meeting, $item]) : route('board-agenda.store', $meeting),
        'resolution' => $item->exists ? route('board-resolutions.update', [$meeting, $item]) : route('board-resolutions.store', $meeting),
        default => route('board-follow-ups.store', $resolution),
    };
@endphp
<div class="container">
    <h1 class="h2">{{ $heading }} {{ $item->exists ? 'bearbeiten' : 'anlegen' }}</h1>
    <p class="text-secondary">Sitzung: {{ $meeting->title }}@if ($kind === 'task') · Beschluss #{{ $resolution->id }}: {{ $resolution->title }}@endif</p>
    <form class="card card-body" action="{{ $action }}" method="POST">
        @csrf
        @if ($item->exists) @method('PUT') @endif
        <x-validation-errors />
        <div class="mb-3"><label for="title" class="form-label">Titel</label><input class="form-control" id="title" name="title" maxlength="180" value="{{ old('title', $item->title) }}" required></div>
        @if ($kind === 'agenda')
            <div class="mb-3"><label for="position" class="form-label">Reihenfolge</label><input class="form-control" type="number" min="1" max="999" step="1" id="position" name="position" value="{{ old('position', $item->position) }}" required><div class="form-text">Eine freie Nummer wählen, zum Beispiel 1, 2, 3. Nummernlücken sind erlaubt.</div></div>
            <div class="mb-3"><label for="minutes" class="form-label">Protokoll zum Thema (optional)</label><textarea class="form-control" id="minutes" name="minutes" rows="7" maxlength="20000">{{ old('minutes', $item->minutes) }}</textarea></div>
        @endif
        @if ($kind !== 'resolution')
            <div class="mb-3"><label for="description" class="form-label">Beschreibung (optional)</label><textarea class="form-control" id="description" name="description" rows="4" maxlength="5000">{{ old('description', $item->description) }}</textarea></div>
        @endif
        @if ($kind === 'resolution')
            <div class="mb-3"><label for="board_agenda_item_id" class="form-label">Tagesordnungspunkt (optional)</label><select class="form-select" id="board_agenda_item_id" name="board_agenda_item_id"><option value="">Ohne Zuordnung</option>@foreach ($meeting->agendaItems as $topic)<option value="{{ $topic->id }}" @selected(old('board_agenda_item_id', $item->board_agenda_item_id) == $topic->id)>{{ $topic->position }}. {{ $topic->title }}</option>@endforeach</select></div>
            <div class="mb-3"><label for="body" class="form-label">Beschlusstext</label><textarea class="form-control" id="body" name="body" rows="7" maxlength="20000" required>{{ old('body', $item->body) }}</textarea><div class="form-text">Den konkreten Wortlaut festhalten. Bei späteren Korrekturen auf die ursprüngliche Beschlussreferenz verweisen.</div></div>
            <div class="mb-3"><label for="result" class="form-label">Ergebnis</label><select class="form-select" id="result" name="result" required>@foreach (App\Enums\BoardResolutionResult::cases() as $result)<option value="{{ $result->value }}" @selected(old('result', $item->result?->value) === $result->value)>{{ $result->label() }}</option>@endforeach</select></div>
            <fieldset class="mb-3"><legend class="h6">Abstimmung (optional)</legend><div class="row g-3">
                @foreach (['votes_for' => 'Ja-Stimmen', 'votes_against' => 'Nein-Stimmen', 'votes_abstained' => 'Enthaltungen'] as $field => $label)
                    <div class="col-md-4"><label for="{{ $field }}" class="form-label">{{ $label }}</label><input class="form-control" type="number" min="0" max="10000" step="1" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $item->$field) }}"></div>
                @endforeach
            </div><div class="form-text">Alle drei Zahlen eintragen (auch 0) oder alle leer lassen. Das Ergebnis wird bewusst ausgewählt, nicht aus einer unbekannten Satzungsregel abgeleitet.</div></fieldset>
        @endif
        @if ($kind === 'task')
            <div class="mb-3"><label for="due_at" class="form-label">Fällig am</label><input class="form-control" id="due_at" name="due_at" type="date" value="{{ old('due_at') }}" required><div class="form-text">Ab diesem Tag erscheint ein Hinweis, bis die Aufgabe als erledigt markiert wird.</div></div>
            <div class="mb-3"><label for="assigned_to" class="form-label">Zuständig (optional)</label><select class="form-select" id="assigned_to" name="assigned_to"><option value="">Noch offen: Vorstandsverwaltung übernimmt</option>@foreach ($assignees as $assignee)<option value="{{ $assignee->id }}" @selected(old('assigned_to') == $assignee->id)>{{ $assignee->name }}</option>@endforeach</select><div class="form-text">Nur Personen mit Zugriff auf Vorstandsunterlagen sind auswählbar. Auftrag und Zuständigkeit bleiben als Historie erhalten.</div></div>
        @endif
        <div class="d-flex gap-2"><button class="btn btn-primary">Speichern</button><a class="btn btn-outline-secondary" href="{{ route('board-meetings.show', $meeting) }}">Abbrechen</a></div>
    </form>
</div>
@endsection
