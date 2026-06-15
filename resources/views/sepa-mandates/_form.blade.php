@csrf
@if ($mandate->exists)
    @method('PUT')
@endif
<x-validation-errors />
<div class="alert alert-info">
    Das schriftlich oder elektronisch erteilte Mandat bleibt die rechtliche Grundlage. {{ config('app.name', 'OKGV') }} speichert die für den Einzug erforderlichen Angaben verschlüsselt; das Originaldokument wird später in der Dokumentenverwaltung hinterlegt.
</div>
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="member_id">Mitglied</label>
        <select class="form-select" id="member_id" name="member_id" required>
            @foreach ($members as $member)
                <option value="{{ $member->id }}" @selected((string) old('member_id', $mandate->member_id) === (string) $member->id)>{{ $member->member_number }} · {{ $member->full_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="mandate_reference">Mandatsreferenz</label>
        <input class="form-control text-uppercase" id="mandate_reference" name="mandate_reference" maxlength="35" @required($mandate->exists) value="{{ old('mandate_reference', $mandate->mandate_reference) }}" placeholder="{{ $mandate->exists ? '' : 'Wird automatisch vergeben' }}">
        <div class="form-text">{{ $mandate->exists ? 'Vereinsweit eindeutig, höchstens 35 SEPA-Zeichen.' : 'Leer lassen, um die nächste Mandatsreferenz automatisch zu vergeben.' }} Leerzeichen werden automatisch durch Bindestriche ersetzt.</div>
    </div>
    <div class="col-md-8">
        <label class="form-label" for="iban">IBAN</label>
        <input class="form-control text-uppercase" id="iban" name="iban" required value="{{ old('iban', $mandate->iban) }}" autocomplete="off">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="bic">BIC (optional)</label>
        <input class="form-control text-uppercase" id="bic" name="bic" maxlength="11" value="{{ old('bic', $mandate->bic) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="account_holder">Kontoinhaber</label>
        <input class="form-control" id="account_holder" name="account_holder" maxlength="70" required value="{{ old('account_holder', $mandate->account_holder) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label" for="mandate_type">Mandatsart</label>
        <select class="form-select" id="mandate_type" name="mandate_type" required>
            @foreach ($types as $type)
                <option value="{{ $type->value }}" @selected(old('mandate_type', $mandate->mandate_type?->value) === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label" for="status">Status</label>
        <select class="form-select" id="status" name="status" required>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $mandate->status?->value) === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="signed_at">Unterschrieben am</label>
        <input class="form-control" type="date" id="signed_at" name="signed_at" required value="{{ old('signed_at', $mandate->signed_at?->format('Y-m-d')) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="valid_from">Gültig ab</label>
        <input class="form-control" type="date" id="valid_from" name="valid_from" required value="{{ old('valid_from', $mandate->valid_from?->format('Y-m-d')) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="valid_until">Gültig bis</label>
        <input class="form-control" type="date" id="valid_until" name="valid_until" value="{{ old('valid_until', $mandate->valid_until?->format('Y-m-d')) }}">
        <div class="form-text">Leer lassen, solange kein Ende feststeht.</div>
    </div>
</div>
<div class="d-flex gap-2 mt-4">
    <button class="btn btn-primary">Mandat speichern</button>
    <a class="btn btn-outline-secondary" href="{{ route('sepa-mandates.index') }}">Abbrechen</a>
</div>
