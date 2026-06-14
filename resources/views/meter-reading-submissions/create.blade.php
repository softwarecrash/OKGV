@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-2">Zählerstand melden</h1>
    <p class="text-secondary">Parzelle {{ $meter->parcel->parcel_number }} · {{ $meter->type->label() }} · Zähler {{ $meter->meter_number }}</p>
    <form class="card card-body border-0 shadow-sm" method="POST" enctype="multipart/form-data" action="{{ route('meter-reading-submissions.store', $meter) }}">
        @csrf
        <x-validation-errors />
        <div class="alert alert-info">Deine Meldung wird zuerst geprüft. Sie zählt erst nach Bestätigung durch Vorstand oder Wasserwart als offizieller Zählerstand.</div>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="reading_value">Abgelesener Stand</label>
                <input class="form-control" type="number" step="0.0001" min="0" id="reading_value" name="reading_value" value="{{ old('reading_value') }}" required inputmode="decimal">
                <div class="form-text">Alle sichtbaren Ziffern einschließlich Nachkommastellen eingeben.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="reading_date">Ablesedatum</label>
                <input class="form-control" type="date" id="reading_date" name="reading_date" max="{{ now()->format('Y-m-d') }}" value="{{ old('reading_date', now()->format('Y-m-d')) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="photo">Foto (optional)</label>
                <input class="form-control" type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
                <div class="form-text">JPEG, PNG oder WebP, höchstens 8 MiB. Das Foto bleibt privat.</div>
            </div>
            <div class="col-12">
                <label class="form-label" for="notes">Hinweis (optional)</label>
                <textarea class="form-control" id="notes" name="notes" rows="3" maxlength="2000" placeholder="Zum Beispiel: Zähler war schwer ablesbar.">{{ old('notes') }}</textarea>
            </div>
        </div>
        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary">Zur Prüfung einreichen</button>
            <a class="btn btn-outline-secondary" href="{{ route('tenant-portal.index') }}">Abbrechen</a>
        </div>
    </form>
</div>
@endsection
