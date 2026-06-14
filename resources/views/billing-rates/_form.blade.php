@csrf
@if ($billingRate->exists)
    @method('PUT')
@endif

<x-validation-errors />

<div class="alert alert-info">
    Ein Preis beschreibt eine Rechnungsposition. Berechnungsart und Geltungsbereich bestimmen gemeinsam, für wen und in welcher Menge sie berechnet wird.
</div>

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="code">Interner Schlüssel</label>
        <input class="form-control text-uppercase" id="code" name="code" maxlength="100" required
               value="{{ old('code', $billingRate->code) }}" placeholder="LEASE_PER_SQM"
               x-on:input="$el.value = $el.value.toUpperCase().replace(/\s+/g, '_')">
        <div class="form-text">Eindeutige Kurzbezeichnung, zum Beispiel PACHT_PRO_QM. Leerzeichen werden automatisch ersetzt.</div>
    </div>
    <div class="col-md-8">
        <label class="form-label" for="name">Bezeichnung</label>
        <input class="form-control" id="name" name="name" maxlength="255" required
               value="{{ old('name', $billingRate->name) }}" placeholder="Pacht pro Quadratmeter">
        <div class="form-text">Diese verständliche Bezeichnung erscheint auf der Rechnung.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="calculation_type">Berechnungsart</label>
        <select class="form-select" id="calculation_type" name="calculation_type" required>
            @foreach ($types as $type)
                <option value="{{ $type->value }}" @selected(old('calculation_type', $billingRate->calculation_type?->value) === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
        <div class="form-text">Festbetrag = einmalig; pro m²/kWh/m³ = Menge mal Preis; manuell = ausdrücklich zugewiesene Position.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="scope">Geltungsbereich</label>
        <select class="form-select" id="scope" name="scope" required>
            @foreach ($scopes as $scope)
                <option value="{{ $scope->value }}" @selected(old('scope', $billingRate->scope?->value) === $scope->value)>{{ $scope->label() }}</option>
            @endforeach
        </select>
        <div class="form-text">Mitglied = einmal je Person; Parzelle = je zugeordneter Parzelle; Zuordnung = nur für ausgewählte Personen oder Parzellen.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="amount">Betrag in Euro</label>
        <input class="form-control" type="number" id="amount" name="amount" min="0" step="0.0001" required
               value="{{ old('amount', $billingRate->amount) }}">
        <div class="form-text">Preis pro gewählter Einheit. Vier Nachkommastellen sind möglich.</div>
    </div>
    <div class="col-12">
        <label class="form-label" for="description">Beschreibung</label>
        <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $billingRate->description) }}</textarea>
        <div class="form-text">Optionaler interner Hinweis zur Herkunft oder Verwendung des Preises.</div>
    </div>
    <div class="col-12">
        <div class="form-check">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                   @checked(old('is_active', $billingRate->is_active ?? true))>
            <label class="form-check-label" for="is_active">Preis aktiv verwenden</label>
            <div class="form-text">Inaktive Preise bleiben dokumentiert, werden aber nicht in neue Berechnungen aufgenommen.</div>
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button class="btn btn-primary">Speichern</button>
    <a class="btn btn-outline-secondary" href="{{ route('billing-periods.show', $billingPeriod) }}">Abbrechen</a>
</div>
