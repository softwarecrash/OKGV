@extends('layouts.app')

@section('content')
<div class="container">
    <div class="mb-4">
        <h1 class="h2 mb-1">Zählerstand korrigieren</h1>
        <span class="text-secondary">
            {{ $meterReading->meter->meter_number }} · Parzelle {{ $meterReading->meter->parcel->parcel_number }}
        </span>
    </div>

    <div class="alert alert-warning">
        Der ursprüngliche Zählerstand wird nicht überschrieben. Die Korrektur
        wird mit Begründung, Benutzer und Zeitpunkt dauerhaft protokolliert.
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header">Bestehender Stand</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-6">Ablesedatum</dt>
                        <dd class="col-6">{{ $meterReading->reading_date->format('d.m.Y') }}</dd>
                        <dt class="col-6">Originalwert</dt>
                        <dd class="col-6">{{ $meterReading->reading_value }} {{ $meterReading->meter->type->unit() }}</dd>
                        <dt class="col-6">Wirksamer Wert</dt>
                        <dd class="col-6">{{ $meterReading->effective_reading_value }} {{ $meterReading->meter->type->unit() }}</dd>
                        <dt class="col-6">Quelle</dt>
                        <dd class="col-6">{{ $meterReading->source->label() }}</dd>
                    </dl>
                </div>
            </div>

            @if ($meterReading->corrections->isNotEmpty())
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header">Bisherige Korrekturen</div>
                    <div class="list-group list-group-flush">
                        @foreach ($meterReading->corrections as $correction)
                            <div class="list-group-item">
                                <strong>{{ $correction->corrected_value }} {{ $meterReading->meter->type->unit() }}</strong>
                                <div class="small text-secondary">
                                    {{ $correction->created_at->format('d.m.Y H:i') }}
                                    · {{ $correction->corrector->name }}
                                </div>
                                <div class="mt-1">{{ $correction->reason }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-7">
            <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('meter-reading-corrections.store', $meterReading) }}">
                @csrf
                <x-validation-errors />

                <div class="mb-3">
                    <label class="form-label" for="corrected_value">Korrigierter Stand</label>
                    <div class="input-group">
                        <input class="form-control" type="number" min="0" step="0.0001"
                               id="corrected_value" name="corrected_value"
                               value="{{ old('corrected_value', $meterReading->effective_reading_value) }}" required>
                        <span class="input-group-text">{{ $meterReading->meter->type->unit() }}</span>
                    </div>
                    <div class="form-text">Dieser Wert wird künftig für Anzeige und Verbrauchsberechnung verwendet. Der Originalwert bleibt erhalten.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="reason">Begründung</label>
                    <textarea class="form-control" id="reason" name="reason" rows="5"
                              minlength="10" maxlength="2000" required>{{ old('reason') }}</textarea>
                    <div class="form-text">Mindestens 10 Zeichen. Keine sensiblen Daten eintragen.</div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary">Korrektur speichern</button>
                    <a class="btn btn-outline-secondary" href="{{ route('meters.show', $meterReading->meter) }}">Abbrechen</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
