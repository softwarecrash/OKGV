@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-4">SEPA-Einstellungen</h1>
    <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('sepa-settings.update') }}">
        @csrf
        @method('PUT')
        <x-validation-errors />
        <div class="alert alert-warning">
            Diese Daten werden in jede Sammellastschrift übernommen. IBAN und BIC werden verschlüsselt gespeichert. Prüfe Gläubiger-ID und Vereinskonto besonders sorgfältig.
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="creditor_name">Name des Zahlungsempfängers</label>
                <input class="form-control" id="creditor_name" name="creditor_name" maxlength="70" required value="{{ old('creditor_name', $settings->creditor_name) }}">
                <div class="form-text">Offizieller Vereinsname, wie er beim Kreditinstitut geführt wird.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="creditor_identifier">Gläubiger-Identifikationsnummer</label>
                <input class="form-control text-uppercase" id="creditor_identifier" name="creditor_identifier" maxlength="35" required value="{{ old('creditor_identifier', $settings->creditor_identifier) }}" placeholder="DE98ZZZ09999999999">
                <div class="form-text">Von der Deutschen Bundesbank vergebene Gläubiger-ID.</div>
            </div>
            <div class="col-md-8">
                <label class="form-label" for="iban">IBAN des Vereinskontos</label>
                <input class="form-control text-uppercase" id="iban" name="iban" required value="{{ old('iban', $settings->iban) }}" autocomplete="off">
                <div class="form-text">Leerzeichen sind erlaubt und werden automatisch entfernt.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="bic">BIC (optional)</label>
                <input class="form-control text-uppercase" id="bic" name="bic" maxlength="11" value="{{ old('bic', $settings->bic) }}">
                <div class="form-text">Kann bei IBAN-basierten SEPA-Zahlungen meist leer bleiben.</div>
            </div>
            <div class="col-12">
                <input type="hidden" name="batch_booking" value="0">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="batch_booking" name="batch_booking" value="1" @checked(old('batch_booking', $settings->batch_booking ?? true))>
                    <label class="form-check-label" for="batch_booking">Sammelbuchung bei der Bank anfordern</label>
                    <div class="form-text">Die tatsächliche Darstellung auf dem Kontoauszug bestimmt das Kreditinstitut.</div>
                </div>
            </div>
        </div>
        <button class="btn btn-primary mt-4">SEPA-Einstellungen speichern</button>
    </form>
</div>
@endsection
