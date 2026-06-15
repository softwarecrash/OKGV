@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Lageplan</h1>
            <p class="text-secondary mb-0">Interaktive Übersicht der für dich sichtbaren Parzellen.</p>
        </div>
        <a class="btn btn-outline-primary" href="{{ route('parcels.index') }}">Zur Parzellenliste</a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-3 mb-3" aria-label="Legende">
                <span><span class="badge rounded-pill" style="background:#2E7D32">Frei / vergeben</span></span>
                <span><span class="badge rounded-pill text-dark" style="background:#F9A825">Reserviert / gekündigt</span></span>
                <span><span class="badge rounded-pill" style="background:#C62828">Gesperrt</span></span>
            </div>

            @if ($placedParcels->isEmpty())
                <div class="text-center py-5">
                    <strong>Noch keine sichtbare Parzelle im Lageplan platziert.</strong>
                    <div class="text-secondary mt-1">
                        @can('create', App\Models\Parcel::class)
                            Öffne eine Parzelle und trage beim Bearbeiten Position, Breite und Höhe ein.
                        @else
                            Die Lageplanpositionen werden durch den Verein gepflegt.
                        @endcan
                    </div>
                </div>
            @else
                <div class="ratio border rounded overflow-hidden bg-body-tertiary" style="--bs-aspect-ratio: 66.6667%;">
                    <svg
                        class="w-100 h-100"
                        viewBox="0 0 1200 800"
                        role="img"
                        aria-labelledby="parcel-map-title parcel-map-description"
                        preserveAspectRatio="xMidYMid meet">
                        <title id="parcel-map-title">Lageplan der Kleingartenanlage</title>
                        <desc id="parcel-map-description">Klickbare Parzellenflächen mit Nummer, Status und Größe.</desc>
                        <defs>
                            <pattern id="map-grid" width="50" height="50" patternUnits="userSpaceOnUse">
                                <path d="M 50 0 L 0 0 0 50" fill="none" stroke="currentColor" stroke-opacity="0.08" stroke-width="1"/>
                            </pattern>
                        </defs>
                        <rect width="1200" height="800" fill="currentColor" fill-opacity="0.025"/>
                        <rect width="1200" height="800" fill="url(#map-grid)"/>

                        @foreach ($placedParcels as $parcel)
                            <a href="{{ route('parcels.show', $parcel) }}" aria-label="Parzelle {{ $parcel->parcel_number }}, {{ $parcel->status->label() }}, {{ number_format((float) $parcel->area_sqm, 2, ',', '.') }} Quadratmeter">
                                <title>Parzelle {{ $parcel->parcel_number }} · {{ $parcel->status->label() }} · {{ number_format((float) $parcel->area_sqm, 2, ',', '.') }} m²</title>
                                <rect
                                    x="{{ $parcel->map_x }}"
                                    y="{{ $parcel->map_y }}"
                                    width="{{ $parcel->map_width }}"
                                    height="{{ $parcel->map_height }}"
                                    rx="8"
                                    fill="{{ $parcel->status->mapColor() }}"
                                    stroke="var(--bs-body-color)"
                                    stroke-width="2"
                                    vector-effect="non-scaling-stroke"/>
                                <text
                                    x="{{ $parcel->map_x + ($parcel->map_width / 2) }}"
                                    y="{{ $parcel->map_y + ($parcel->map_height / 2) }}"
                                    fill="{{ $parcel->status->mapTextColor() }}"
                                    font-size="{{ min(30, max(14, (int) ($parcel->map_width / 5))) }}"
                                    font-weight="700"
                                    text-anchor="middle"
                                    dominant-baseline="middle">
                                    {{ $parcel->parcel_number }}
                                </text>
                            </a>
                        @endforeach
                    </svg>
                </div>
                <p class="small text-secondary mt-3 mb-0">Wähle eine Fläche aus, um die Parzellendetails zu öffnen. Der Status wird zusätzlich beim Überfahren und für Hilfstechnologien als Text ausgegeben.</p>
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
