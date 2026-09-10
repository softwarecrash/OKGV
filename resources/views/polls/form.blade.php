@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2">{{ $poll->exists ? 'Umfrage bearbeiten' : 'Umfrage oder Terminabfrage anlegen' }}</h1>
    <p class="text-secondary">Zuerst als Entwurf speichern. Nach Veröffentlichung sind Fragen, Zielgruppe und Zeitraum nicht mehr änderbar. Dies ist eine unverbindliche Meinungs- oder Terminabfrage, keine förmliche Vereinsabstimmung.</p>
    @php
        $options = old('options', $poll->options->map(fn ($option) => ['label' => $option->label, 'starts_at' => $option->starts_at?->format('Y-m-d\TH:i'), 'ends_at' => $option->ends_at?->format('Y-m-d\TH:i')])->all());
    @endphp
    <form method="POST" action="{{ $poll->exists ? route('polls.update', $poll) : route('polls.store') }}" class="card card-body" x-data="{ count: {{ max(3, count($options)) }} }">
        @csrf
        @if ($poll->exists) @method('PUT') @endif
        <x-validation-errors />
        <div class="mb-3">
            <label class="form-label" for="title">Titel / Frage</label>
            <input class="form-control" id="title" name="title" maxlength="180" value="{{ old('title', $poll->title) }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="description">Beschreibung (optional)</label>
            <textarea class="form-control" id="description" name="description" rows="4" maxlength="10000">{{ old('description', $poll->description) }}</textarea>
        </div>
        <div class="row g-3 mb-3">
            @foreach (['type' => ['Art', $types], 'audience' => ['Zielgruppe', $audiences], 'results_visibility' => ['Ergebnisse', $visibilities]] as $field => [$label, $values])
                <div class="col-md-4">
                    <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                    <select class="form-select" name="{{ $field }}" id="{{ $field }}" required>
                        @foreach ($values as $value)<option value="{{ $value->value }}" @selected(old($field, $poll->$field?->value) === $value->value)>{{ $value->label() }}</option>@endforeach
                    </select>
                </div>
            @endforeach
        </div>
        <p class="form-text">Nur bereits bestätigte und freigegebene Konten der Zielgruppe werden bei Veröffentlichung eingeladen. Eine Antwort je Konto, auch bei mehreren Parzellen. Vertrauliche Ergebnisse sind nicht anonym: Antworten werden mit dem Konto gespeichert. Vor Abschluss sieht niemand eine Auswertung.</p>
        <fieldset class="mb-3">
            <legend class="h6">Rollen (nur bei Zielgruppe „Ausgewählte Rollen“)</legend>
            @foreach ($roles as $role)
                <div class="form-check form-check-inline">
                    <input type="checkbox" class="form-check-input" name="roles[]" value="{{ $role->value }}" id="role-{{ $role->value }}" @checked(in_array($role->value, old('roles', $poll->roles ?? []), true))>
                    <label class="form-check-label" for="role-{{ $role->value }}">{{ $role->label() }}</label>
                </div>
            @endforeach
        </fieldset>
        <div class="row g-3 mb-3">
            @foreach (['starts_at' => 'Abstimmung beginnt', 'ends_at' => 'Abstimmung endet'] as $field => $label)
                <div class="col-md-6">
                    <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                    <input type="datetime-local" class="form-control" name="{{ $field }}" id="{{ $field }}" value="{{ old($field, $poll->$field?->format('Y-m-d\TH:i')) }}" required>
                </div>
            @endforeach
        </div>
        <p class="form-text">Zeitzone: {{ config('app.timezone') }}. Mit Fristablauf wird die Teilnahme automatisch geschlossen.</p>
        <div class="form-check mb-3">
            <input type="hidden" name="multiple" value="0">
            <input type="checkbox" class="form-check-input" id="multiple" name="multiple" value="1" @checked(old('multiple', $poll->multiple))>
            <label class="form-check-label" for="multiple">Mehrere Antworten erlauben (bei Terminabfragen immer aktiv)</label>
        </div>
        <h2 class="h5">Antwortmöglichkeiten</h2>
        <p class="form-text">Mindestens zwei, höchstens 20. Unbenutzte Zeilen vollständig leer lassen. Nur bei Terminabfragen Beginn und optional Ende eintragen; Termine müssen nach der Abstimmungsfrist liegen. Es werden keine Arbeitseinsätze automatisch gebucht.</p>
        @for ($i = 0; $i < 20; $i++)
            <fieldset class="border rounded p-3 mb-3" x-show="count > {{ $i }}">
                <legend class="float-none w-auto h6 px-1">Antwort {{ $i + 1 }}</legend>
                <label class="form-label" for="option-{{ $i }}">Beschriftung</label>
                <input class="form-control mb-2" id="option-{{ $i }}" name="options[{{ $i }}][label]" maxlength="180" value="{{ $options[$i]['label'] ?? '' }}">
                <div class="row g-2">
                    @foreach (['starts_at' => 'Terminbeginn', 'ends_at' => 'Terminende (optional)'] as $field => $label)
                        <div class="col-md-6">
                            <label class="form-label" for="option-{{ $i }}-{{ $field }}">{{ $label }}</label>
                            <input type="datetime-local" class="form-control" id="option-{{ $i }}-{{ $field }}" name="options[{{ $i }}][{{ $field }}]" value="{{ $options[$i][$field] ?? '' }}">
                        </div>
                    @endforeach
                </div>
            </fieldset>
        @endfor
        <button class="btn btn-outline-secondary align-self-start mb-3" type="button" x-show="count < 20" @click="count++">Weitere Antwort hinzufügen</button>
        <div class="d-flex gap-2"><button class="btn btn-primary">Entwurf speichern</button><a class="btn btn-outline-secondary" href="{{ route('polls.index', ['manage' => 1]) }}">Abbrechen</a></div>
    </form>
</div>
@endsection
