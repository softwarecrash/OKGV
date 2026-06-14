@csrf
@if ($workEvent->exists)
    @method('PUT')
@endif

<div class="alert alert-info">
    Der Einsatz muss vollständig innerhalb der Abrechnungsperiode liegen. Erst nach dem Termin kann er abgeschlossen und können Teilnahmen bestätigt werden.
</div>

<div class="mb-3">
    <label class="form-label" for="title">Bezeichnung</label>
    <input class="form-control @error('title') is-invalid @enderror"
           id="title" name="title" maxlength="255"
           value="{{ old('title', $workEvent->title) }}" required>
    <div class="form-text">Zum Beispiel „Frühjahrsputz Gemeinschaftsfläche“.</div>
    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="starts_at">Beginn</label>
        <input class="form-control @error('starts_at') is-invalid @enderror"
               id="starts_at" name="starts_at" type="datetime-local"
               value="{{ old('starts_at', $workEvent->starts_at?->format('Y-m-d\TH:i')) }}" required>
        @error('starts_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="ends_at">Ende</label>
        <input class="form-control @error('ends_at') is-invalid @enderror"
               id="ends_at" name="ends_at" type="datetime-local"
               value="{{ old('ends_at', $workEvent->ends_at?->format('Y-m-d\TH:i')) }}" required>
        @error('ends_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="location">Ort</label>
        <input class="form-control @error('location') is-invalid @enderror"
               id="location" name="location" maxlength="255"
               value="{{ old('location', $workEvent->location) }}">
        @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="status">Status</label>
        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $workEvent->status?->value) === $status->value)>
                    {{ $status->label() }}
                </option>
            @endforeach
        </select>
        <div class="form-text">„Abgeschlossen“ aktiviert die Bestätigung geleisteter Stunden. „Abgesagt“ entfernt bereits übernommene Stunden wieder aus den Jahreskonten.</div>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="mt-3">
    <label class="form-label" for="description">Beschreibung für die Durchführung</label>
    <textarea class="form-control @error('description') is-invalid @enderror"
              id="description" name="description" rows="4" maxlength="10000">{{ old('description', $workEvent->description) }}</textarea>
    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mt-3">
    <label class="form-label" for="notes">Interne Notizen</label>
    <textarea class="form-control @error('notes') is-invalid @enderror"
              id="notes" name="notes" rows="3" maxlength="10000">{{ old('notes', $workEvent->notes) }}</textarea>
    <div class="form-text">Interne Hinweise erscheinen nicht auf Rechnungen.</div>
    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="d-flex gap-2 mt-4">
    <button class="btn btn-primary">{{ $workEvent->exists ? 'Änderungen speichern' : 'Arbeitseinsatz anlegen' }}</button>
    <a class="btn btn-outline-secondary" href="{{ $workEvent->exists ? route('work-events.show', $workEvent) : route('billing-periods.show', $billingPeriod) }}">Abbrechen</a>
</div>
