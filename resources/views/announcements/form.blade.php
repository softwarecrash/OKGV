@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2">{{ $announcement->exists ? 'Entwurf bearbeiten' : 'Beitrag anlegen' }}</h1>
    <p class="text-secondary">Speichere zuerst einen Entwurf. Nach dem Veröffentlichen bleiben Text und Zielgruppe unverändert; bei Korrekturen kannst du den Beitrag zurückziehen und einen neuen anlegen.</p>
    <form method="POST" action="{{ $announcement->exists ? route('announcements.update', $announcement) : route('announcements.store') }}" class="card card-body shadow-sm border-0">
        @csrf
        @if ($announcement->exists) @method('PUT') @endif
        <x-validation-errors />
        <div class="mb-3">
            <label class="form-label" for="title">Titel</label>
            <input class="form-control" id="title" name="title" maxlength="180" value="{{ old('title', $announcement->title) }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="body">Mitteilung</label>
            <textarea class="form-control" id="body" name="body" rows="9" maxlength="20000" required>{{ old('body', $announcement->body) }}</textarea>
            <div class="form-text">Schreibe den Text mit Absätzen. HTML-Code wird nicht ausgeführt.</div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="audience">Wer soll den Beitrag sehen?</label>
            <select class="form-select" id="audience" name="audience" required>
                @foreach ($audiences as $audience)
                    <option value="{{ $audience->value }}" @selected(old('audience', $announcement->audience?->value) === $audience->value)>{{ $audience->label() }}</option>
                @endforeach
            </select>
            <div class="form-text">„Öffentlich“ ist ohne Anmeldung im Internet lesbar. „Aktuelle Pächter“ umfasst auch Vorstände mit eigener Parzelle.</div>
        </div>
        <fieldset class="mb-3">
            <legend class="h6">Rollen auswählen</legend>
            <div class="form-text mb-2">Nur für die Zielgruppe „Ausgewählte Rollen“ erforderlich. Technische Administratorrechte zählen hier nicht als Vereinsrolle.</div>
            @foreach ($roles as $role)
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="roles[]" id="role-{{ $role->value }}" value="{{ $role->value }}" @checked(in_array($role->value, old('roles', $announcement->roles ?? []), true))>
                    <label class="form-check-label" for="role-{{ $role->value }}">{{ $role->label() }}</label>
                </div>
            @endforeach
        </fieldset>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label" for="starts_at">Sichtbar ab</label>
                <input class="form-control" type="datetime-local" name="starts_at" id="starts_at" value="{{ old('starts_at', $announcement->starts_at?->format('Y-m-d\TH:i')) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="ends_at">Sichtbar bis (optional)</label>
                <input class="form-control" type="datetime-local" name="ends_at" id="ends_at" value="{{ old('ends_at', $announcement->ends_at?->format('Y-m-d\TH:i')) }}">
                <div class="form-text">Leer lassen für eine unbefristete Veröffentlichung. Zeitangaben gelten in {{ config('app.timezone') }}.</div>
            </div>
        </div>
        @foreach (['is_highlighted' => 'Beitrag hervorheben', 'requires_confirmation' => 'Ausdrückliche Lesebestätigung erbitten'] as $field => $label)
            <div class="form-check mb-2">
                <input type="hidden" name="{{ $field }}" value="0">
                <input class="form-check-input" type="checkbox" id="{{ $field }}" name="{{ $field }}" value="1" @checked(old($field, $announcement->$field))>
                <label class="form-check-label" for="{{ $field }}">{{ $label }}</label>
            </div>
        @endforeach
        @if (App\Enums\FeatureModule::Documents->enabled())
            <fieldset class="mt-3">
                <legend class="h6">Dokumente verknüpfen (optional, höchstens 10)</legend>
                <p class="form-text">Anhänge werden in der Dokumentenverwaltung hochgeladen und freigegeben. Jeder Leser benötigt weiterhin Zugriff auf das Dokument. Öffentliche Beiträge dürfen nur öffentliche Dokumente enthalten.</p>
                @can('create', App\Models\Document::class)<a href="{{ route('documents.create') }}" target="_blank" rel="noopener">Dokument in neuem Tab anlegen</a>@endcan
                @forelse ($documents as $document)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="document_ids[]" id="document-{{ $document->id }}" value="{{ $document->id }}" @checked(in_array($document->id, old('document_ids', $announcement->documents->modelKeys())))>
                        <label class="form-check-label" for="document-{{ $document->id }}">{{ $document->title }} · {{ $document->visibility->label() }}</label>
                    </div>
                @empty
                    <p class="text-secondary">Keine freigegebenen Dokumente verfügbar.</p>
                @endforelse
            </fieldset>
        @endif
        <div class="d-flex gap-2 mt-4"><button class="btn btn-primary">Entwurf speichern</button><a class="btn btn-outline-secondary" href="{{ route('announcements.index', ['manage' => 1]) }}">Abbrechen</a></div>
    </form>
</div>
@endsection
