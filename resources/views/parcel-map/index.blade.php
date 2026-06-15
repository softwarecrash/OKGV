@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Lageplan</h1>
            <p class="text-secondary mb-0">Interaktive Übersicht auf dem hinterlegten Luft- oder Lagebild.</p>
        </div>
        <div class="d-flex gap-2">
            @can('manageMap', App\Models\Parcel::class)
                <a class="btn btn-primary" href="{{ route('parcel-map.edit') }}">Lageplan bearbeiten</a>
            @endcan
            <a class="btn btn-outline-primary" href="{{ route('parcels.index') }}">Zur Parzellenliste</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-3 mb-3" aria-label="Legende">
                <span><span class="badge rounded-pill" style="background:#2E7D32">Frei / vergeben</span></span>
                <span><span class="badge rounded-pill text-dark" style="background:#F9A825">Reserviert / gekündigt</span></span>
                <span><span class="badge rounded-pill" style="background:#C62828">Gesperrt</span></span>
            </div>

            @if (! $settings->map_background_path)
                <div class="alert alert-warning mb-4">
                    Es ist noch kein Hintergrundbild hinterlegt.
                    @can('manageMap', App\Models\Parcel::class)
                        Öffne den Bearbeitungsmodus und lade ein rechtmäßig nutzbares Luft- oder Lagebild hoch.
                    @endcan
                </div>
            @endif

            @if ($placedParcels->isEmpty())
                <div class="text-center py-5">
                    <strong>Noch keine sichtbare Parzelle im Lageplan platziert.</strong>
                    <div class="text-secondary mt-1">
                        @can('manageMap', App\Models\Parcel::class)
                            Öffne den Bearbeitungsmodus und zeichne die erste Parzellenfläche.
                        @else
                            Die Lageplanpositionen werden durch den Verein gepflegt.
                        @endcan
                    </div>
                </div>
            @else
                <div
                    data-parcel-map-zoom
                    data-width="{{ $settings->map_background_width }}"
                    data-height="{{ $settings->map_background_height }}">
                    <div class="parcel-map-toolbar mb-2" aria-label="Kartengröße">
                        <div class="btn-group" role="group" aria-label="Lageplan vergrößern oder verkleinern">
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-map-zoom-out title="Verkleinern">−</button>
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-map-zoom-reset>Einpassen</button>
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-map-zoom-in title="Vergrößern">+</button>
                        </div>
                        <span class="small text-secondary" data-map-zoom-label aria-live="polite">100 %</span>
                    </div>
                    <div class="parcel-map-viewport" data-map-viewport>
                        <svg
                            class="parcel-map-canvas"
                            viewBox="0 0 {{ $settings->map_background_width }} {{ $settings->map_background_height }}"
                            role="img"
                            aria-labelledby="parcel-map-title parcel-map-description"
                            preserveAspectRatio="xMidYMid meet"
                            data-map-zoom-target>
                            <title id="parcel-map-title">Lageplan der Kleingartenanlage</title>
                            <desc id="parcel-map-description">Klickbare Parzellenflächen mit Nummer, Status und Größe.</desc>
                            @if ($settings->map_background_path)
                                <image
                                    href="{{ route('parcel-map.background', ['v' => $settings->updated_at?->timestamp]) }}"
                                    width="{{ $settings->map_background_width }}"
                                    height="{{ $settings->map_background_height }}"
                                    preserveAspectRatio="none"/>
                            @else
                                <rect width="100%" height="100%" fill="currentColor" fill-opacity="0.04"/>
                            @endif

                            @foreach ($placedParcels as $parcel)
                                <a href="{{ route('parcels.show', $parcel) }}" aria-label="Parzelle {{ $parcel->parcel_number }}, {{ $parcel->status->label() }}, {{ number_format((float) $parcel->area_sqm, 2, ',', '.') }} Quadratmeter">
                                    <title>Parzelle {{ $parcel->parcel_number }} · {{ $parcel->status->label() }} · {{ number_format((float) $parcel->area_sqm, 2, ',', '.') }} m²</title>
                                    <polygon
                                        points="{{ collect($parcel->map_polygon)->map(fn ($point) => $point['x'].','.$point['y'])->implode(' ') }}"
                                        fill="{{ $parcel->status->mapColor() }}"
                                        fill-opacity="0.42"
                                        stroke="var(--bs-body-color)"
                                        stroke-width="3"
                                        vector-effect="non-scaling-stroke"/>
                                    @php
                                        $centerX = collect($parcel->map_polygon)->avg('x');
                                        $centerY = collect($parcel->map_polygon)->avg('y');
                                    @endphp
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
                                </a>
                            @endforeach
                        </svg>
                    </div>
                </div>
                <p class="small text-secondary mt-3 mb-0">Vergrößere den Plan mit den Schaltflächen oder mit Strg und Mausrad. Greife den vergrößerten Plan mit gedrückter Maustaste, um den Ausschnitt zu verschieben. Ein kurzer Klick auf eine Parzelle öffnet weiterhin ihre Details.</p>
            @endif
        </div>
    </div>

    @if ($unplacedParcels->isNotEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-body">
                <h2 class="h5 mb-0">Noch nicht platzierte Parzellen</h2>
            </div>
            <div class="list-group list-group-flush">
                @foreach ($unplacedParcels as $parcel)
                    <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <strong>Parzelle {{ $parcel->parcel_number }}</strong>
                            <span class="text-secondary">· {{ $parcel->status->label() }} · {{ number_format((float) $parcel->area_sqm, 2, ',', '.') }} m²</span>
                        </div>
                        <div class="d-flex gap-2">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('parcels.show', $parcel) }}">Details</a>
                            @can('update', $parcel)
                                <a class="btn btn-sm btn-primary" href="{{ route('parcels.edit', $parcel) }}">Platzieren</a>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
