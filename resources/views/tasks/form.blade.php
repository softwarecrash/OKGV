@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2">{{ $task->exists ? 'Aufgabe bearbeiten' : 'Aufgabe anlegen' }}</h1>
    <p class="text-secondary">Aufgabeninhalt ist für zuständige Personen und berechtigte Verwalter sichtbar. Vertrauliche Beschlussaufgaben erfordern zusätzlich Vorstandsleserecht.</p>
    <form method="POST" action="{{ $task->exists ? route('tasks.update', $task) : route('tasks.store') }}" class="card card-body">
        @csrf
        @if ($task->exists) @method('PUT') @endif
        <x-validation-errors />
        <div class="mb-3"><label for="title" class="form-label">Aufgabe</label><input class="form-control" id="title" name="title" maxlength="180" value="{{ old('title', $task->title) }}" required></div>
        <div class="mb-3"><label for="description" class="form-label">Beschreibung (optional)</label><textarea class="form-control" id="description" name="description" rows="5" maxlength="5000">{{ old('description', $task->description) }}</textarea></div>
        <div class="row g-3 mb-3">
            <div class="col-md-6"><label for="due_at" class="form-label">Fällig am</label><input class="form-control" type="date" id="due_at" name="due_at" value="{{ old('due_at', $task->due_at?->format('Y-m-d')) }}" required></div>
            <div class="col-md-6"><label for="remind_at" class="form-label">Wiedervorlage ab (optional)</label><input class="form-control" type="date" id="remind_at" name="remind_at" value="{{ old('remind_at', $task->remind_at?->format('Y-m-d')) }}"><div class="form-text">Ab diesem Tag erscheint ein Hinweis. Leer lassen: Hinweis ab Fälligkeit. Keine zusätzliche E-Mail.</div></div>
        </div>
        <div class="mb-3"><label for="assigned_to" class="form-label">Zuständig</label><select class="form-select" id="assigned_to" name="assigned_to"><option value="">Noch nicht zugewiesen</option>@if ($task->assigned_to && ! $assignees->contains('id', $task->assigned_to))<option value="{{ $task->assigned_to }}" @selected(old('assigned_to', $task->assigned_to) == $task->assigned_to)>Bisheriges Konto: Rechte fehlen, bitte neu zuweisen</option>@endif @foreach ($assignees as $assignee)<option value="{{ $assignee->id }}" @selected(old('assigned_to', $task->assigned_to) == $assignee->id)>{{ $assignee->name }}</option>@endforeach</select><div class="form-text">Für allgemeine Aufgaben benötigt die Person das Recht „Eigene Verwaltungsaufgaben bearbeiten“. Für Beschlussaufgaben reicht Vorstandsleserecht. Ohne Zuweisung ist die Verwaltung zuständig.</div></div>
        <div class="mb-3"><label for="recurrence" class="form-label">Wiederholung</label><select class="form-select" id="recurrence" name="recurrence">@foreach (App\Enums\TaskRecurrence::cases() as $recurrence)<option value="{{ $recurrence->value }}" @selected(old('recurrence', $task->recurrence?->value ?? 'none') === $recurrence->value)>{{ $recurrence->label() }}</option>@endforeach</select><div class="form-text">Beim Erledigen entsteht genau ein Folgetermin im ursprünglichen Rhythmus. Versäumte Termine werden nicht übersprungen. Zum Beenden vor Abschluss „Keine“ wählen. Eine geänderte Fälligkeit startet den Rhythmus neu.</div></div>
        <fieldset class="mb-3"><legend class="h5">Verknüpfungen (optional)</legend><p class="form-text">Eine Verknüpfung gewährt keine zusätzlichen Rechte auf Stammdaten oder Dokumente.</p>
            @foreach (['member_id' => ['Mitglied', $members, 'full_name'], 'parcel_id' => ['Parzelle', $parcels, 'parcel_number'], 'document_id' => ['Dokument', $documents, 'title']] as $field => [$label, $options, $nameField])
                @if (($field === 'document_id' && ! App\Enums\FeatureModule::Documents->enabled()) || ($task->$field && ! $options->contains('id', $task->$field)))
                    <p class="text-secondary">{{ $label }}: Verknüpfung derzeit nicht zugreifbar. Sie bleibt unverändert erhalten.</p>
                @else
                    <div class="mb-3"><label for="{{ $field }}" class="form-label">{{ $label }}</label><select class="form-select" id="{{ $field }}" name="{{ $field }}"><option value="">Keine Verknüpfung</option>@foreach ($options as $option)<option value="{{ $option->id }}" @selected(old($field, $task->$field) == $option->id)>{{ $option->$nameField }}</option>@endforeach</select></div>
                @endif
            @endforeach
            @if (! $task->exists && $resolutions->isNotEmpty())
                <label for="board_resolution_id" class="form-label">Beschluss</label><select class="form-select" id="board_resolution_id" name="board_resolution_id"><option value="">Ohne Beschlussbezug</option>@foreach ($resolutions as $resolution)<option value="{{ $resolution->id }}" @selected(old('board_resolution_id') == $resolution->id)>#{{ $resolution->id }}: {{ $resolution->title }}</option>@endforeach</select><div class="form-text">Die gewählte Beschlusszuordnung bleibt nach dem Anlegen unveränderbar.</div>
            @elseif ($task->board_resolution_id)
                <p>Beschlussreferenz #{{ $task->board_resolution_id }} bleibt erhalten.</p>
            @endif
        </fieldset>
        <div class="d-flex gap-2"><button class="btn btn-primary">Speichern</button><a class="btn btn-outline-secondary" href="{{ $task->exists ? route('tasks.show', $task) : route('tasks.index') }}">Abbrechen</a></div>
    </form>
</div>
@endsection
