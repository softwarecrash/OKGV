<x-validation-errors />
<div class="alert alert-info">Die Parzellennummer muss eindeutig sein. Zugeordnete Personen werden nach dem Speichern separat und mit einem gültigen Zeitraum erfasst.</div>
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
        <div class="form-text">Der Status beschreibt die aktuelle Nutzbarkeit; die Zuordnungshistorie wird unabhängig davon geführt.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="use_type">Nutzungsart</label>
        <select class="form-select" id="use_type" name="use_type" required>
            @foreach ($useTypes as $useType)
                <option value="{{ $useType->value }}" @selected(old('use_type', $parcel->use_type?->value ?? 'lease') === $useType->value)>{{ $useType->label() }}</option>
            @endforeach
        </select>
        <div class="form-text">Die Nutzungsart gehört dauerhaft zur Parzelle, nicht zur zugeordneten Person.</div>
    </div>
    <div class="col-md-6 d-flex align-items-end">
        <div class="form-check mb-2">
            <input type="hidden" name="has_operating_permit" value="0">
            <input class="form-check-input" type="checkbox" id="has_operating_permit" name="has_operating_permit" value="1"
                   @checked(old('has_operating_permit', $parcel->has_operating_permit ?? false))>
            <label class="form-check-label" for="has_operating_permit">Betreibergenehmigung liegt vor</label>
            <div class="form-text">Wird als Parzellenmerkmal dokumentiert. Zusätzliche Rechte werden erst nach fachlicher Klärung daraus abgeleitet.</div>
        </div>
    </div>
    <div class="col-12">
        <label class="form-label" for="location_description">Lagebeschreibung</label>
        <input class="form-control" id="location_description" name="location_description" value="{{ old('location_description', $parcel->location_description) }}">
        <div class="form-text">Optional, zum Beispiel „Nordweg, dritte Parzelle links“.</div>
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
