<x-validation-errors />

<div class="alert alert-info">
    Wartelisteneinträge enthalten personenbezogene Kontaktdaten und sind nur für ausdrücklich berechtigte Vereinskonten sichtbar. Einträge werden über ihren Status abgeschlossen und nicht gelöscht.
</div>

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="first_name">Vorname</label>
        <input class="form-control @error('first_name') is-invalid @enderror"
               id="first_name"
               name="first_name"
               value="{{ old('first_name', $entry->first_name) }}"
               maxlength="255"
               autocomplete="given-name"
               required>
        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="last_name">Nachname</label>
        <input class="form-control @error('last_name') is-invalid @enderror"
               id="last_name"
               name="last_name"
               value="{{ old('last_name', $entry->last_name) }}"
               maxlength="255"
               autocomplete="family-name"
               required>
        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="email">E-Mail-Adresse</label>
        <input class="form-control @error('email') is-invalid @enderror"
               id="email"
               name="email"
               type="email"
               value="{{ old('email', $entry->email) }}"
               maxlength="255"
               autocomplete="email"
               required>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="phone">Telefon</label>
        <input class="form-control @error('phone') is-invalid @enderror"
               id="phone"
               name="phone"
               type="tel"
               value="{{ old('phone', $entry->phone) }}"
               maxlength="50"
               autocomplete="tel">
        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="mobile">Mobil</label>
        <input class="form-control @error('mobile') is-invalid @enderror"
               id="mobile"
               name="mobile"
               type="tel"
               value="{{ old('mobile', $entry->mobile) }}"
               maxlength="50">
        @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="applied_at">Eingangsdatum</label>
        <input class="form-control @error('applied_at') is-invalid @enderror"
               id="applied_at"
               name="applied_at"
               type="date"
               value="{{ old('applied_at', $entry->applied_at?->format('Y-m-d')) }}"
               required>
        <div class="form-text">Datum der ersten Anfrage beim Verein.</div>
        @error('applied_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="priority">Priorität</label>
        <select class="form-select @error('priority') is-invalid @enderror"
                id="priority"
                name="priority"
                required>
            @foreach (range(1, 5) as $priority)
                <option value="{{ $priority }}" @selected((int) old('priority', $entry->priority ?? 3) === $priority)>
                    {{ $priority }}{{ $priority === 1 ? ' – höchste' : ($priority === 5 ? ' – niedrigste' : '') }}
                </option>
            @endforeach
        </select>
        <div class="form-text">1 wird zuerst bearbeitet, 5 zuletzt. Bei gleicher Priorität steht die ältere Anfrage oben.</div>
        @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="status">Status</label>
        <select class="form-select @error('status') is-invalid @enderror"
                id="status"
                name="status"
                required>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $entry->status?->value ?? 'waiting') === $status->value)>
                    {{ $status->label() }}
                </option>
            @endforeach
        </select>
        <div class="form-text">Wartend, Kontaktiert und Angebot unterbreitet gelten als offene Vorgänge.</div>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label" for="notes">Interne Notizen</label>
        <textarea class="form-control @error('notes') is-invalid @enderror"
                  id="notes"
                  name="notes"
                  rows="5"
                  maxlength="10000">{{ old('notes', $entry->notes) }}</textarea>
        <div class="form-text">Zum Beispiel gewünschte Gartengröße oder bisherige Kontakte. Nur notwendige Angaben speichern.</div>
        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button class="btn btn-primary" type="submit">Speichern</button>
    <a class="btn btn-outline-secondary" href="{{ $entry->exists ? route('waiting-list-entries.show', $entry) : route('waiting-list-entries.index') }}">Abbrechen</a>
</div>
