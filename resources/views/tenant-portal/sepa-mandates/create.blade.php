@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h1 class="h4 mb-0">SEPA-Mandat hinterlegen</h1>
                </div>
                <div class="card-body">
                    <x-validation-errors />

                    <p class="text-secondary">
                        Mit diesem Formular erteilst du dem Verein die Einwilligung,
                        fällige Beträge per SEPA-Lastschrift von deinem Konto einzuziehen.
                    </p>

                    <form method="POST" action="{{ route('tenant-portal.sepa-mandates.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label" for="iban">IBAN</label>
                            <input class="form-control text-uppercase"
                                   id="iban"
                                   name="iban"
                                   value="{{ old('iban') }}"
                                   autocomplete="off"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="bic">BIC (optional)</label>
                            <input class="form-control text-uppercase"
                                   id="bic"
                                   name="bic"
                                   maxlength="11"
                                   value="{{ old('bic') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="account_holder">Kontoinhaber</label>
                            <input class="form-control"
                                   id="account_holder"
                                   name="account_holder"
                                   maxlength="70"
                                   value="{{ old('account_holder', auth()->user()->member?->full_name) }}"
                                   required>
                        </div>

                        <div class="form-check border rounded p-3 ps-5 mb-4">
                            <input class="form-check-input" type="checkbox" id="consent" name="consent" value="1" required>
                            <label class="form-check-label" for="consent">
                                Ich ermächtige den Verein, fällige Zahlungen per SEPA-Lastschrift einzuziehen.
                                Zugleich weise ich mein Kreditinstitut an, die Lastschriften einzulösen.
                                Ich kann das Mandat jederzeit für zukünftige Einzüge widerrufen.
                            </label>
                        </div>

                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" type="submit">Mandat speichern</button>
                            <a class="btn btn-outline-secondary" href="{{ route('tenant-portal.sepa-mandates.index') }}">Abbrechen</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
