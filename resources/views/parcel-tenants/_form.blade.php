<x-validation-errors />
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="parcel_id">Parzelle</label>
        <select class="form-select" id="parcel_id" name="parcel_id" required>
            @foreach ($parcels as $parcel)
                <option value="{{ $parcel->id }}" @selected((string) old('parcel_id', $parcelTenant->parcel_id) === (string) $parcel->id)>{{ $parcel->parcel_number }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="member_id">Mitglied</label>
        <select class="form-select" id="member_id" name="member_id" required>
            @foreach ($members as $member)
                <option value="{{ $member->id }}" @selected((string) old('member_id', $parcelTenant->member_id) === (string) $member->id)>{{ $member->member_number }} · {{ $member->full_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="starts_at">Beginn</label>
        <input class="form-control" type="date" id="starts_at" name="starts_at" value="{{ old('starts_at', $parcelTenant->starts_at?->format('Y-m-d')) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="ends_at">Ende</label>
        <input class="form-control" type="date" id="ends_at" name="ends_at" value="{{ old('ends_at', $parcelTenant->ends_at?->format('Y-m-d')) }}">
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <div class="form-check mb-2">
            <input type="hidden" name="is_primary" value="0">
            <input class="form-check-input" type="checkbox" id="is_primary" name="is_primary" value="1" @checked(old('is_primary', $parcelTenant->is_primary))>
            <label class="form-check-label" for="is_primary">Hauptpächter</label>
        </div>
    </div>
    <div class="col-12">
        <label class="form-label" for="notes">Interne Notizen</label>
        <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $parcelTenant->notes) }}</textarea>
    </div>
</div>
<div class="d-flex gap-2 mt-4">
    <button class="btn btn-primary" type="submit">Speichern</button>
    <a class="btn btn-outline-secondary" href="{{ $parcelTenant->parcel_id ? route('parcels.show', $parcelTenant->parcel_id) : route('parcels.index') }}">Abbrechen</a>
</div>
