@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-4">Zähler anlegen</h1>

    <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('meters.store') }}">
        @csrf
        <x-validation-errors />

        <div class="alert alert-info">
            Ein Zähler wird fest einer Parzelle und einer Verbrauchsart zugeordnet. Startstand und Einbaudatum bilden den Anfang der unveränderlichen Verbrauchshistorie.
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="parcel_id">Parzelle</label>
                <select class="form-select" id="parcel_id" name="parcel_id" required>
                    @foreach ($parcels as $parcel)
                        <option value="{{ $parcel->id }}" @selected((string) old('parcel_id', $meter->parcel_id) === (string) $parcel->id)>{{ $parcel->parcel_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="type">Verbrauchsart</label>
                <select class="form-select" id="type" name="type" required>
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
                <div class="form-text">Wasser wird in m³, Strom in kWh geführt.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="meter_number">Zählernummer</label>
                <input class="form-control" id="meter_number" name="meter_number" value="{{ old('meter_number') }}" required>
                <div class="form-text">Vom Gerät ablesen; die Nummer muss systemweit eindeutig sein.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="installed_at">Einbaudatum</label>
                <input class="form-control" type="date" id="installed_at" name="installed_at" value="{{ old('installed_at', $meter->installed_at?->format('Y-m-d')) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="start_reading">Startstand</label>
                <input class="form-control" type="number" min="0" step="0.0001" id="start_reading" name="start_reading" value="{{ old('start_reading') }}" required>
                <div class="form-text">Stand direkt beim Einbau, ohne Einheit eingeben.</div>
            </div>
            <div class="col-12">
                <label class="form-label" for="notes">Interne Notizen</label>
                <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                <div class="form-text">Nur für berechtigte Vereinskonten sichtbar.</div>
            </div>
        </div>
        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary">Zähler speichern</button>
            <a class="btn btn-outline-secondary" href="{{ route('meters.index') }}">Abbrechen</a>
        </div>
    </form>
</div>
@endsection
