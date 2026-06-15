@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Lageplan bearbeiten</h1>
            <p class="text-secondary mb-0">Hintergrundbild verwalten und beliebige Parzellenformen direkt darauf zeichnen.</p>
        </div>
        <a class="btn btn-outline-primary" href="{{ route('parcel-map.index') }}">Fertigen Lageplan anzeigen</a>
    </div>

    <x-validation-errors />

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5">Hintergrundbild</h2>
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
                        <button class="btn btn-outline-primary">Hintergrundbild speichern</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div
        class="card border-0 shadow-sm"
        data-parcel-map-editor
        data-parcel-map-zoom
        data-map-handle-radius="9"
        data-width="{{ $settings->map_background_width }}"
        data-height="{{ $settings->map_background_height }}">
        <div class="card-body">
            <div class="row g-3 align-items-end mb-3">
                <div class="col-lg-6">
                    <label class="form-label" for="parcel-map-selection">Parzelle auswählen</label>
                    <select class="form-select" id="parcel-map-selection" data-map-parcel-select>
                        <option value="">Bitte auswählen</option>
                        @foreach ($parcels as $parcel)
                            <option
                                value="{{ $parcel->id }}"
                                data-number="{{ $parcel->parcel_number }}"
                                data-color="{{ $parcel->status->mapColor() }}"
                                data-polygon="{{ json_encode($parcel->map_polygon ?? [], JSON_THROW_ON_ERROR) }}"
                                data-action="{{ route('parcel-map.polygon.update', $parcel) }}">
                                {{ $parcel->parcel_number }} · {{ $parcel->status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-6">
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-outline-primary" type="button" data-map-draw disabled>Punkte zeichnen</button>
                        <button class="btn btn-outline-secondary" type="button" data-map-undo disabled>Letzten Punkt entfernen</button>
                        <button class="btn btn-outline-danger" type="button" data-map-clear disabled>Fläche entfernen</button>
                    </div>
                </div>
            </div>

            <div class="alert alert-info" data-map-help>
                Wähle eine Parzelle. Setze anschließend mindestens drei Punkte. Eckpunkte lassen sich ziehen; die gefüllte Fläche kann als Ganzes verschoben werden.
            </div>

            <div class="parcel-map-toolbar mb-2" aria-label="Kartengröße">
                <div class="btn-group" role="group" aria-label="Lageplan vergrößern oder verkleinern">
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-map-zoom-out title="Verkleinern">−</button>
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-map-zoom-reset>Einpassen</button>
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-map-zoom-in title="Vergrößern">+</button>
                </div>
                <span class="small text-secondary" data-map-zoom-label aria-live="polite">100 %</span>
            </div>

            <div class="parcel-map-viewport parcel-map-editor-frame" data-map-viewport>
                <svg
                    class="parcel-map-canvas"
                    viewBox="0 0 {{ $settings->map_background_width }} {{ $settings->map_background_height }}"
                    preserveAspectRatio="xMidYMid meet"
                    data-map-svg
                    data-map-zoom-target>
                    @if ($settings->map_background_path)
                        <image
                            href="{{ route('parcel-map.background', ['v' => $settings->updated_at?->timestamp]) }}"
                            width="{{ $settings->map_background_width }}"
                            height="{{ $settings->map_background_height }}"
                            preserveAspectRatio="none"/>
                    @else
                        <rect width="100%" height="100%" fill="currentColor" fill-opacity="0.04"/>
                        <text x="50%" y="50%" text-anchor="middle" dominant-baseline="middle" fill="currentColor">Bitte zuerst ein Hintergrundbild hochladen</text>
                    @endif
                    <polygon class="parcel-map-editor-polygon" data-map-polygon points=""/>
                    <g data-map-handles></g>
                </svg>
            </div>
            <p class="small text-secondary mt-2 mb-0">Nutze die Zoomschaltflächen oder Strg und Mausrad. Ziehe freie Bildfläche mit gedrückter Maustaste, um den Ausschnitt zu verschieben. Eckpunkte und die markierte Parzellenfläche bleiben direkt bearbeitbar.</p>

            <form class="mt-3" method="POST" data-map-form>
                @csrf
                @method('PUT')
                <input type="hidden" name="polygon" value="[]" data-map-polygon-input>
                <input type="hidden" name="remove_polygon" value="0" data-map-remove-input>
                <button class="btn btn-primary" type="submit" data-map-save disabled>Fläche speichern</button>
                <span class="text-secondary ms-2" data-map-point-count>0 Punkte</span>
            </form>
        </div>
    </div>
</div>
@endsection
