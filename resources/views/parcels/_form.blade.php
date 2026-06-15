<x-validation-errors />
<div class="alert alert-info">Die Parzellennummer muss eindeutig sein. Pächter werden nach dem Speichern separat und mit einem gültigen Vertragszeitraum zugeordnet.</div>
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="parcel_number">Parzellennummer</label>
        <input class="form-control" id="parcel_number" name="parcel_number" value="{{ old('parcel_number', $parcel->parcel_number) }}" required>
        <div class="form-text">Die im Verein gebräuchliche Nummer oder Bezeichnung.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="area_sqm">Fläche in m²</label>
        <input class="form-control" type="number" min="0.01" step="0.01" id="area_sqm" name="area_sqm" value="{{ old('area_sqm', $parcel->area_sqm) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="status">Status</label>
        <select class="form-select" id="status" name="status" required>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $parcel->status?->value ?? 'free') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        <div class="form-text">Der Status beschreibt die aktuelle Nutzbarkeit; die Pächterhistorie wird unabhängig davon geführt.</div>
    </div>
    <div class="col-12">
        <label class="form-label" for="location_description">Lagebeschreibung</label>
        <input class="form-control" id="location_description" name="location_description" value="{{ old('location_description', $parcel->location_description) }}">
        <div class="form-text">Optional, zum Beispiel „Nordweg, dritte Parzelle links“.</div>
    </div>
    <div class="col-12">
        <div class="card bg-body-tertiary border-0">
            <div class="card-body">
                <h2 class="h6">Position im Lageplan</h2>
                <p class="text-secondary small">Alle vier Werte ausfüllen, um die Parzelle als Rechteck auf dem Lageplan zu platzieren. Leer lassen, solange die Position noch nicht feststeht.</p>
                <div class="row g-3">
                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label" for="map_x">X-Position</label>
                        <input class="form-control" type="number" min="0" max="1199" id="map_x" name="map_x" value="{{ old('map_x', $parcel->map_x) }}">
                        <div class="form-text">Abstand vom linken Rand, 0 bis 1199.</div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label" for="map_y">Y-Position</label>
                        <input class="form-control" type="number" min="0" max="799" id="map_y" name="map_y" value="{{ old('map_y', $parcel->map_y) }}">
                        <div class="form-text">Abstand vom oberen Rand, 0 bis 799.</div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label" for="map_width">Breite</label>
                        <input class="form-control" type="number" min="20" max="1200" id="map_width" name="map_width" value="{{ old('map_width', $parcel->map_width) }}">
                        <div class="form-text">Mindestens 20 Einheiten.</div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label" for="map_height">Höhe</label>
                        <input class="form-control" type="number" min="20" max="800" id="map_height" name="map_height" value="{{ old('map_height', $parcel->map_height) }}">
                        <div class="form-text">Mindestens 20 Einheiten.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12">
        <label class="form-label" for="notes">Interne Notizen</label>
        <textarea class="form-control" id="notes" name="notes" rows="4">{{ old('notes', $parcel->notes) }}</textarea>
        <div class="form-text">Nur für berechtigte Vereinskonten sichtbar.</div>
    </div>
</div>
<div class="d-flex gap-2 mt-4">
    <button class="btn btn-primary" type="submit">Speichern</button>
    <a class="btn btn-outline-secondary" href="{{ $parcel->exists ? route('parcels.show', $parcel) : route('parcels.index') }}">Abbrechen</a>
</div>
