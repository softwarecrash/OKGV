<div class="card-body">
    <div class="alert alert-info">
        Dateien werden privat gespeichert. Beim späteren Ersetzen bleibt jede ältere Version erhalten.
        Pächter sehen ein Dokument erst nach Veröffentlichung und nur bei passender Zuordnung.
    </div>

    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label" for="title">Titel</label>
            <input class="form-control" id="title" name="title" required maxlength="255"
                   value="{{ old('title', $document->title) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="type">Dokumenttyp</label>
            <select class="form-select" id="type" name="type" required>
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected(old('type', $document->type?->value) === $type->value)>
                        {{ $type->label() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-12">
            <label class="form-label" for="description">Beschreibung</label>
            <textarea class="form-control" id="description" name="description" rows="3" maxlength="5000">{{ old('description', $document->description) }}</textarea>
            <div class="form-text">Optionaler interner Kontext zum Inhalt oder zur Ablage.</div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="member_id">Mitglied</label>
            <select class="form-select" id="member_id" name="member_id">
                <option value="">Kein Mitglied zuordnen</option>
                @foreach ($members as $member)
                    <option value="{{ $member->id }}" @selected((string) old('member_id', $document->member_id) === (string) $member->id)>
                        {{ $member->member_number }} – {{ $member->last_name }}, {{ $member->first_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="parcel_id">Parzelle</label>
            <select class="form-select" id="parcel_id" name="parcel_id">
                <option value="">Keine Parzelle zuordnen</option>
                @foreach ($parcels as $parcel)
                    <option value="{{ $parcel->id }}" @selected((string) old('parcel_id', $document->parcel_id) === (string) $parcel->id)>
                        Parzelle {{ $parcel->parcel_number }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="visibility">Sichtbarkeit</label>
            <select class="form-select" id="visibility" name="visibility" required>
                @foreach ($visibilities as $visibility)
                    <option value="{{ $visibility->value }}" @selected(old('visibility', $document->visibility?->value ?? 'internal') === $visibility->value)>
                        {{ $visibility->label() }}
                    </option>
                @endforeach
            </select>
            <div class="form-text">
                „Intern“ bleibt im Vorstand. „Pächter“ benötigt eine Zuordnung. „Öffentlich“ erzeugt einen geheimen Freigabelink.
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="file">{{ $document->exists ? 'Neue Dateiversion' : 'Datei' }}</label>
            <input class="form-control" id="file" name="file" type="file"
                   accept=".pdf,.jpg,.jpeg,.png,.webp,.txt,.docx,.xlsx" @required(! $document->exists)>
            <div class="form-text">
                PDF, Bild, TXT, DOCX oder XLSX, maximal 20 MiB. Programme, HTML, SVG und Makrodateien sind nicht erlaubt.
                @if ($document->exists)
                    Ohne Auswahl bleibt Version {{ $document->current_version }} unverändert.
                @endif
            </div>
        </div>
        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" id="published" name="published" type="checkbox" value="1"
                       @checked(old('published', $document->published_at !== null))>
                <label class="form-check-label" for="published">Jetzt veröffentlichen</label>
            </div>
            <div class="form-text">Die Veröffentlichung kann jederzeit zurückgenommen werden. Interne Dokumente bleiben trotzdem nur für Berechtigte sichtbar.</div>
        </div>
    </div>

    <x-validation-errors />
</div>
<div class="card-footer bg-body border-0">
    <button class="btn btn-primary">{{ $document->exists ? 'Änderungen speichern' : 'Dokument hochladen' }}</button>
    <a class="btn btn-outline-secondary" href="{{ $document->exists ? route('documents.show', $document) : route('documents.index') }}">Abbrechen</a>
</div>
