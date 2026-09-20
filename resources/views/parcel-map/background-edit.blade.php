@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Lageplan-Hintergrund verwalten</h1>
            <p class="text-secondary mb-0">Luftbild oder Lageplan für die Parzellenzeichnung hinterlegen oder ersetzen.</p>
        </div>
        <a class="btn btn-outline-primary" href="{{ route('parcel-map.edit') }}">Zur Parzellenbearbeitung</a>
    </div>

    <x-validation-errors />

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <p class="text-secondary">
                Verwende ein eigenes oder ausdrücklich lizenziertes Luftbild, einen Katasterauszug oder einen selbst erstellten Plan.
                Ein Screenshot aus Google Maps darf nicht automatisch als frei nutzbares Bild behandelt werden. Google-Satellitendaten müssen über eine zulässige Google-Maps-API-Einbindung angezeigt werden.
            </p>
            @if ($settings->map_background_path)
                <div class="alert alert-info">
                    Aktuell: <strong>{{ $settings->map_background_original_name }}</strong>
                    · {{ $settings->map_background_width }} × {{ $settings->map_background_height }} Pixel
                    · Quelle: {{ $settings->map_background_source }}
                </div>
            @endif
            <form method="POST" action="{{ route('parcel-map.background.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-lg-6">
                        <label class="form-label" for="background">Neues Luft- oder Lagebild</label>
                        <input class="form-control" id="background" name="background" type="file" accept="image/jpeg,image/png,image/webp" required>
                        <div class="form-text">JPEG, PNG oder WebP, höchstens 15 MiB und maximal 12.000 × 12.000 Pixel.</div>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label" for="source">Quelle und Nutzungsrecht</label>
                        <input class="form-control" id="source" name="source" maxlength="255" required value="{{ old('source', $settings->map_background_source) }}" placeholder="Eigenes Drohnenfoto / Geoportal-Lizenz / selbst erstellter Plan">
                        <div class="form-text">Dokumentiere nachvollziehbar, woher das Bild stammt und warum es verwendet werden darf.</div>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" id="rights_confirmed" name="rights_confirmed" type="checkbox" value="1" required>
                            <label class="form-check-label" for="rights_confirmed">Ich bestätige, dass der Verein dieses Bild für den Lageplan speichern und anzeigen darf.</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary">Hintergrundbild speichern</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
