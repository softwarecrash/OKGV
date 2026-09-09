@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2">{{ $meeting->exists ? 'Sitzung bearbeiten' : 'Vorstandssitzung anlegen' }}</h1>
    <p class="text-secondary">Nur Personen mit Zugriff auf Vorstandsunterlagen sehen diese Sitzung. Tagesordnung und Beschlüsse ergänzt du nach dem Speichern.</p>
    <form class="card card-body" method="POST" action="{{ $meeting->exists ? route('board-meetings.update', $meeting) : route('board-meetings.store') }}">
        @csrf
        @if ($meeting->exists) @method('PUT') @endif
        <x-validation-errors />
        <div class="mb-3"><label for="title" class="form-label">Titel</label><input class="form-control" id="title" name="title" maxlength="180" value="{{ old('title', $meeting->title) }}" required></div>
        <div class="row g-3 mb-3">
            <div class="col-md-6"><label for="scheduled_at" class="form-label">Sitzungstermin</label><input class="form-control" id="scheduled_at" type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', $meeting->scheduled_at?->format('Y-m-d\TH:i')) }}" required><div class="form-text">Zeitzone: {{ config('app.timezone') }}. Es werden keine Einladungsmails versendet.</div></div>
            <div class="col-md-6"><label for="location" class="form-label">Ort (optional)</label><input class="form-control" id="location" name="location" maxlength="180" value="{{ old('location', $meeting->location) }}"></div>
        </div>
        <div class="mb-3"><label for="attendees" class="form-label">Teilnehmer</label><textarea class="form-control" id="attendees" name="attendees" rows="3" maxlength="5000">{{ old('attendees', $meeting->attendees) }}</textarea><div class="form-text">Namen der anwesenden Personen, gegebenenfalls Sitzungsleitung und Protokollführung. Beim endgültigen Abschluss erforderlich.</div></div>
        <div class="mb-3"><label for="minutes" class="form-label">Allgemeines Protokoll</label><textarea class="form-control" id="minutes" name="minutes" rows="8" maxlength="20000">{{ old('minutes', $meeting->minutes) }}</textarea><div class="form-text">Zum Beispiel Beginn, Feststellung der Beschlussfähigkeit und Ende. Details können zusätzlich je Tagesordnungspunkt erfasst werden.</div></div>
        @if (App\Enums\FeatureModule::Documents->enabled())
            <fieldset class="mb-3"><legend class="h6">Dokumente verknüpfen (optional, höchstens 20)</legend>
                <p class="form-text">Dokumente bleiben unter ihren bisherigen Zugriffsrechten. Verknüpft wird die aktuelle Dateiversion, nicht eine Kopie im Protokoll.</p>
                @can('create', App\Models\Document::class)<a href="{{ route('documents.create') }}" target="_blank" rel="noopener">Dokument in neuem Tab anlegen</a>@endcan
                @forelse ($documents as $document)
                    <div class="form-check"><input class="form-check-input" id="document-{{ $document->id }}" name="document_ids[]" type="checkbox" value="{{ $document->id }}" @checked(in_array($document->id, old('document_ids', $meeting->documents->modelKeys())))><label class="form-check-label" for="document-{{ $document->id }}">{{ $document->title }} · {{ $document->visibility->label() }}</label></div>
                @empty
                    <p class="text-secondary">Keine zugreifbaren Dokumente vorhanden.</p>
                @endforelse
            </fieldset>
        @endif
        <div class="d-flex gap-2"><button class="btn btn-primary">Entwurf speichern</button><a class="btn btn-outline-secondary" href="{{ $meeting->exists ? route('board-meetings.show', $meeting) : route('board-meetings.index') }}">Abbrechen</a></div>
    </form>
</div>
@endsection
