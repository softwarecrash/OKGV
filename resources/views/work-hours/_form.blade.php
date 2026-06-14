@csrf
@if ($workHour->exists)
    @method('PUT')
@endif

<div class="alert alert-info">
    Fehlstunden und Strafzahlung werden automatisch berechnet. Mehr geleistete als geforderte Stunden führen nicht zu einem negativen Betrag.
</div>

<div class="mb-3">
    <label class="form-label" for="member_id">Mitglied</label>
    <select class="form-select @error('member_id') is-invalid @enderror"
            id="member_id"
            name="member_id"
            required
            @disabled($workHour->exists)>
        <option value="">Mitglied auswählen</option>
        @foreach ($members as $member)
            <option value="{{ $member->id }}" @selected((int) old('member_id', $workHour->member_id) === $member->id)>
                {{ $member->last_name }}, {{ $member->first_name }} · {{ $member->member_number }}
            </option>
        @endforeach
    </select>
    @if ($workHour->exists)
        <input type="hidden" name="member_id" value="{{ $workHour->member_id }}">
        <div class="form-text">Das Mitglied kann nach dem Anlegen nicht gewechselt werden. Korrigiere stattdessen die Stundenwerte.</div>
    @else
        <div class="form-text">Pro Mitglied und Abrechnungsperiode kann genau ein Arbeitsstundenkonto geführt werden.</div>
    @endif
    @error('member_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="hours_required">Pflichtstunden</label>
        <div class="input-group">
            <input class="form-control @error('hours_required') is-invalid @enderror"
                   id="hours_required"
                   name="hours_required"
                   type="number"
                   min="0"
                   step="0.25"
                   value="{{ old('hours_required', $workHour->hours_required) }}"
                   required>
            <span class="input-group-text">Std.</span>
            @error('hours_required')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-text">Die laut Vereinsregelung zu leistenden Stunden.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="hours_done">Zusätzlich manuell anerkannte Stunden</label>
        <div class="input-group">
            <input class="form-control @error('hours_done') is-invalid @enderror"
                   id="hours_done"
                   name="hours_done"
                   type="number"
                   min="0"
                   step="0.25"
                   value="{{ old('hours_done', $workHour->manual_hours_done ?? $workHour->hours_done) }}"
                   required>
            <span class="input-group-text">Std.</span>
            @error('hours_done')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-text">Nur Stunden eintragen, die nicht bereits aus einem Arbeitseinsatz übernommen wurden.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="penalty_rate">Betrag je Fehlstunde</label>
        <div class="input-group">
            <input class="form-control @error('penalty_rate') is-invalid @enderror"
                   id="penalty_rate"
                   name="penalty_rate"
                   type="number"
                   min="0"
                   step="0.01"
                   value="{{ old('penalty_rate', $workHour->penalty_rate) }}"
                   required>
            <span class="input-group-text">€</span>
            @error('penalty_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-text">Dieser Betrag wird mit den fehlenden Stunden multipliziert.</div>
    </div>
</div>

<div class="mt-3">
    <label class="form-label" for="notes">Interne Notizen</label>
    <textarea class="form-control @error('notes') is-invalid @enderror"
              id="notes"
              name="notes"
              rows="4"
              maxlength="10000">{{ old('notes', $workHour->notes) }}</textarea>
    <div class="form-text">Zum Beispiel anerkannte Sonderleistungen oder Hinweise zur Berechnung. Diese Notiz erscheint nicht auf der Rechnung.</div>
    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="d-flex gap-2 mt-4">
    <button class="btn btn-primary">{{ $workHour->exists ? 'Änderungen speichern' : 'Arbeitsstundenkonto anlegen' }}</button>
    <a class="btn btn-outline-secondary" href="{{ route('billing-periods.show', $billingPeriod) }}">Abbrechen</a>
</div>
