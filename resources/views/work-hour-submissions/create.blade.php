@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-2">Arbeitsstunden melden</h1>
    <p class="text-secondary mb-4">Melde eine geleistete Tätigkeit für deine Parzelle. Die Stunden zählen erst nach Prüfung.</p>
    <form class="card card-body border-0 shadow-sm" method="POST" enctype="multipart/form-data" action="{{ route('work-hour-submissions.store') }}">
        @csrf
        <x-validation-errors />
        @if ($parcels->isEmpty())
            <div class="alert alert-warning mb-0">
                Deinem Mitgliedskonto ist aktuell keine Parzelle zugeordnet. Bitte wende dich an den Vorstand, bevor du Arbeitsstunden meldest.
            </div>
        @else
            <div class="alert alert-info">Beschreibe die Tätigkeit nachvollziehbar. Ein Foto ist optional und bleibt ausschließlich für berechtigte Prüfer sichtbar.</div>
            <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="parcel_id">Parzelle</label>
                <select class="form-select" id="parcel_id" name="parcel_id" required>
                    @foreach ($parcels as $parcel)
                        <option value="{{ $parcel->id }}" @selected((int) old('parcel_id', $selectedParcelId) === $parcel->id)>Parzelle {{ $parcel->parcel_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="worked_at">Datum</label>
                <input class="form-control" id="worked_at" name="worked_at" type="date" max="{{ today()->format('Y-m-d') }}" value="{{ old('worked_at', today()->format('Y-m-d')) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="hours">Geleistete Stunden</label>
                <div class="input-group">
                    <input class="form-control @error('hours') is-invalid @enderror"
                           id="hours"
                           name="hours"
                           type="number"
                           inputmode="decimal"
                           min="0.25"
                           max="24"
                           step="0.25"
                           placeholder="1"
                           value="{{ old('hours') }}"
                           aria-describedby="hours-help"
                           required>
                    <span class="input-group-text">Std.</span>
                </div>
                <div class="form-text" id="hours-help">In Viertelstunden eingeben, zum Beispiel 1, 1,5 oder 2,25 Stunden.</div>
            </div>
            <div class="col-12">
                <label class="form-label" for="description">Was wurde erledigt?</label>
                <textarea class="form-control" id="description" name="description" rows="4" maxlength="1000" required placeholder="Zum Beispiel: Hecke am Gemeinschaftsweg geschnitten und Schnittgut entsorgt.">{{ old('description') }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label" for="photo">Foto als Nachweis (optional)</label>
                <input class="form-control" id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp">
                <div class="form-text">JPEG, PNG oder WebP, höchstens 8 MiB. Keine Personen ohne deren Einwilligung fotografieren.</div>
            </div>
            </div>
            <div class="d-flex gap-2 mt-4">
                <button class="btn btn-primary">Zur Prüfung einreichen</button>
                <a class="btn btn-outline-secondary" href="{{ route('tenant-portal.index') }}">Abbrechen</a>
            </div>
        @endif
    </form>
</div>
@endsection
