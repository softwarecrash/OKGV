@csrf
@if ($template->exists)
    @method('PUT')
@endif

<x-validation-errors />

<div class="alert alert-info">
    Eine Preisvorlage speichert die wiederkehrende Rechnungslogik. Beim Übernehmen in eine Abrechnungsperiode wird eine eigenständige Kopie erzeugt; spätere Änderungen an der Vorlage verändern keine bestehenden Preise oder Rechnungen.
</div>

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="code">Interner Schlüssel</label>
        <input class="form-control text-uppercase" id="code" name="code" maxlength="100" required
               value="{{ old('code', $template->code) }}" placeholder="PACHT_PRO_QM"
               x-on:input="$el.value = $el.value.toUpperCase().replace(/\s+/g, '_')">
        <div class="form-text">Eindeutige Kurzbezeichnung. Leerzeichen werden automatisch durch Unterstriche ersetzt.</div>
    </div>
    <div class="col-md-8">
        <label class="form-label" for="name">Bezeichnung auf der Rechnung</label>
        <input class="form-control" id="name" name="name" maxlength="255" required
               value="{{ old('name', $template->name) }}" placeholder="Pacht pro Quadratmeter">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="calculation_type">Berechnungsart</label>
        <select class="form-select" id="calculation_type" name="calculation_type" required>
            @foreach ($types as $type)
                <option value="{{ $type->value }}" @selected(old('calculation_type', $template->calculation_type?->value) === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
        <div class="form-text">Legt fest, ob der Betrag fest oder mit Fläche beziehungsweise Verbrauch multipliziert wird.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="scope">Geltungsbereich</label>
        <select class="form-select" id="scope" name="scope" required>
            @foreach ($scopes as $scope)
                <option value="{{ $scope->value }}" @selected(old('scope', $template->scope?->value) === $scope->value)>{{ $scope->label() }}</option>
            @endforeach
        </select>
        <div class="form-text">Bestimmt, ob der Preis für Mitglieder, Parzellen oder nur ausgewählte Zuordnungen gilt.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="default_amount">Vorschlagsbetrag in Euro</label>
        <input class="form-control" type="number" id="default_amount" name="default_amount"
               min="0" step="0.0001" value="{{ old('default_amount', $template->default_amount) }}">
        <div class="form-text">Optional. Der Betrag kann beim Übernehmen für jede Periode geändert werden.</div>
    </div>
    <div class="col-12">
        <label class="form-label" for="description">Interne Beschreibung</label>
        <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $template->description) }}</textarea>
        <div class="form-text">Zum Beispiel Beschlussgrundlage oder Hinweise zur jährlichen Anpassung.</div>
    </div>
    <div class="col-12">
        <div class="form-check">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                   @checked(old('is_active', $template->is_active ?? true))>
            <label class="form-check-label" for="is_active">Vorlage zur Auswahl anbieten</label>
            <div class="form-text">Inaktive Vorlagen bleiben dokumentiert, können aber nicht mehr in neue Perioden übernommen werden.</div>
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button class="btn btn-primary">Vorlage speichern</button>
    <a class="btn btn-outline-secondary" href="{{ route('billing-rate-templates.index') }}">Abbrechen</a>
</div>
