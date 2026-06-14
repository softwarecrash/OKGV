@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-4">Zähler {{ $meter->meter_number }} wechseln</h1>

    <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('meters.replace.store', $meter) }}"
          onsubmit="return confirm('Zählerwechsel endgültig speichern? Der alte Zähler wird zum angegebenen Datum abgeschlossen und ein neuer Zähler angelegt. Die Historie bleibt dauerhaft erhalten.')">
        @csrf
        <x-validation-errors />

        <div class="alert alert-warning">
            <strong>Dauerhafte Historienänderung:</strong> Der bisherige Zähler wird abgeschlossen und kann danach nicht wieder als aktiver Zähler verwendet werden. Prüfe Datum sowie beide Stände direkt am Gerät.
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="replaced_at">Wechseldatum</label>
                <input class="form-control" type="date" id="replaced_at" name="replaced_at" value="{{ old('replaced_at', now()->format('Y-m-d')) }}" required>
                <div class="form-text">Tag, an dem der alte Zähler aus- und der neue eingebaut wurde.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="end_reading">Endstand alter Zähler</label>
                <input class="form-control" type="number" min="0" step="0.0001" id="end_reading" name="end_reading" value="{{ old('end_reading') }}" required>
                <div class="form-text">Letzter Stand des ausgebauten Geräts, ohne Einheit.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="meter_number">Nummer neuer Zähler</label>
                <input class="form-control" id="meter_number" name="meter_number" value="{{ old('meter_number') }}" required>
                <div class="form-text">Muss sich von allen bereits erfassten Zählernummern unterscheiden.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="start_reading">Startstand neuer Zähler</label>
                <input class="form-control" type="number" min="0" step="0.0001" id="start_reading" name="start_reading" value="{{ old('start_reading', '0') }}" required>
                <div class="form-text">Stand des neuen Geräts unmittelbar beim Einbau.</div>
            </div>
            <div class="col-12">
                <label class="form-label" for="notes">Notizen zum neuen Zähler</label>
                <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
            </div>
        </div>
        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-danger">Zählerwechsel speichern</button>
            <a class="btn btn-outline-secondary" href="{{ route('meters.show', $meter) }}">Abbrechen</a>
        </div>
    </form>
</div>
@endsection
