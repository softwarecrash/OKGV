<x-validation-errors />
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="parcel_number">Parzellennummer</label>
        <input class="form-control" id="parcel_number" name="parcel_number" value="{{ old('parcel_number', $parcel->parcel_number) }}" required>
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
    </div>
    <div class="col-12">
        <label class="form-label" for="location_description">Lagebeschreibung</label>
        <input class="form-control" id="location_description" name="location_description" value="{{ old('location_description', $parcel->location_description) }}">
    </div>
    <div class="col-12">
        <label class="form-label" for="notes">Interne Notizen</label>
        <textarea class="form-control" id="notes" name="notes" rows="4">{{ old('notes', $parcel->notes) }}</textarea>
    </div>
</div>
<div class="d-flex gap-2 mt-4">
    <button class="btn btn-primary" type="submit">Speichern</button>
    <a class="btn btn-outline-secondary" href="{{ $parcel->exists ? route('parcels.show', $parcel) : route('parcels.index') }}">Abbrechen</a>
</div>
