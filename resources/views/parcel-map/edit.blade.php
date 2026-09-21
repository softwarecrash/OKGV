@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Lageplan bearbeiten</h1>
            <p class="text-secondary mb-0">Parzellenformen direkt auf dem vorhandenen Lageplan zeichnen und bearbeiten.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('parcel-map.background.edit') }}">Hintergrundbild verwalten</a>
            <a class="btn btn-outline-primary" href="{{ route('parcel-map.index') }}">Fertigen Lageplan anzeigen</a>
        </div>
    </div>

    <div
        class="card border-0 shadow-sm"
        data-parcel-map-editor
        data-parcel-map-zoom
        data-map-handle-radius="9"
        data-map-snap-radius="9"
        data-map-parallel-threshold="10"
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
                                data-action="{{ route('parcel-map.polygon.update', $parcel) }}"
                                @selected($selectedParcelId === $parcel->id)>
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
                            href="{{ route('parcel-map.background', ['v' => hash('sha256', $settings->map_background_path)]) }}"
                            width="{{ $settings->map_background_width }}"
                            height="{{ $settings->map_background_height }}"
                            preserveAspectRatio="none"/>
                    @else
                        <rect width="100%" height="100%" fill="currentColor" fill-opacity="0.04"/>
                        <text x="50%" y="50%" text-anchor="middle" dominant-baseline="middle" fill="currentColor">Bitte zuerst ein Hintergrundbild hochladen</text>
                    @endif
                    @foreach ($parcels->filter(fn ($parcel) => $parcel->isPlacedOnMap()) as $parcel)
                        @php
                            $centerX = collect($parcel->map_polygon)->avg('x');
                            $centerY = collect($parcel->map_polygon)->avg('y');
                        @endphp
                        <g
                            data-map-reference-parcel="{{ $parcel->id }}"
                            data-map-reference-polygon="{{ json_encode($parcel->map_polygon, JSON_THROW_ON_ERROR) }}"
                            pointer-events="none">
                            <polygon
                                points="{{ collect($parcel->map_polygon)->map(fn ($point) => $point['x'].','.$point['y'])->implode(' ') }}"
                                fill="{{ $parcel->status->mapColor() }}"
                                fill-opacity="0.28"
                                stroke="var(--bs-body-color)"
                                stroke-opacity="0.65"
                                stroke-width="3"
                                vector-effect="non-scaling-stroke"/>
                            <text
                                x="{{ $centerX }}"
                                y="{{ $centerY }}"
                                fill="#FFFFFF"
                                stroke="#263238"
                                stroke-width="4"
                                paint-order="stroke"
                                font-size="24"
                                font-weight="700"
                                text-anchor="middle"
                                dominant-baseline="middle">
                                {{ $parcel->parcel_number }}
                            </text>
                        </g>
                    @endforeach
                    <polygon class="parcel-map-editor-polygon" data-map-polygon points="" fill-opacity="0.58" stroke="var(--bs-body-color)" stroke-width="4" vector-effect="non-scaling-stroke"/>
                    <text data-map-active-label fill="#FFFFFF" stroke="#263238" stroke-width="4" paint-order="stroke" font-size="24" font-weight="700" text-anchor="middle" dominant-baseline="middle" pointer-events="none"></text>
                    <line class="parcel-map-parallel-guide" data-map-parallel-line hidden pointer-events="none" vector-effect="non-scaling-stroke"/>
                    <circle class="parcel-map-snap-indicator" data-map-snap-indicator r="7" hidden pointer-events="none" vector-effect="non-scaling-stroke"/>
                    <g data-map-handles></g>
                </svg>
            </div>
            <div class="small text-secondary mt-2" aria-label="Zeichenhilfen">
                <kbd>Strg</kbd>/<kbd>Cmd</kbd> = Einrasten · <kbd>Umschalt</kbd> = Parallel
                <span class="ms-2" data-map-assist-status aria-live="polite"></span>
            </div>
            <p class="small text-secondary mt-2 mb-0">Die übrigen Parzellen sind zur Orientierung sichtbar, aber nicht bearbeitbar. Nutze die Zoomschaltflächen oder Strg und Mausrad. Ziehe freie Bildfläche mit gedrückter Maustaste, um den Ausschnitt zu verschieben. Eckpunkte und die markierte Parzellenfläche bleiben direkt bearbeitbar.</p>

            <form class="mt-3" method="POST" data-map-form>
                @csrf
                @method('PUT')
                <input type="hidden" name="polygon" value="[]" data-map-polygon-input>
                <input type="hidden" name="remove_polygon" value="0" data-map-remove-input>
                <button class="btn btn-primary" type="submit" data-map-save disabled>Fläche speichern</button>
                <span class="text-secondary ms-2" data-map-point-count>0 Punkte</span>
                <span class="ms-2" data-map-save-status aria-live="polite"></span>
            </form>
        </div>
    </div>
</div>
@endsection
