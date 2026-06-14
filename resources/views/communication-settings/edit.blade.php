@extends('layouts.app')

@section('content')
<div class="container">
    <div class="mb-4">
        <h1 class="h2 mb-1">SMTP-Einstellungen</h1>
        <p class="text-secondary mb-0">Konfiguriere den Mailserver für Bestätigungen und Serienmails.</p>
    </div>

    <div class="alert alert-warning">
        Benutzername und Passwort werden verschlüsselt gespeichert und nicht angezeigt.
        Ein leer gelassenes Passwortfeld behält das bestehende Passwort bei.
    </div>

    <form method="POST" action="{{ route('communication-settings.update') }}" class="card border-0 shadow-sm">
        @csrf
        @method('PUT')
        <div class="card-body">
            <input type="hidden" name="smtp_enabled" value="0">
            <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" id="smtp_enabled" name="smtp_enabled" value="1"
                       @checked(old('smtp_enabled', $settings->smtp_enabled))>
                <label class="form-check-label" for="smtp_enabled">SMTP-Versand aktivieren</label>
            </div>

            <div class="row g-3">
                <div class="col-lg-6">
                    <label class="form-label" for="smtp_host">SMTP-Server</label>
                    <input class="form-control" id="smtp_host" name="smtp_host" required maxlength="255"
                           value="{{ old('smtp_host', $settings->smtp_host) }}" placeholder="smtp.example.de">
                </div>
                <div class="col-lg-3">
                    <label class="form-label" for="smtp_port">Port</label>
                    <input class="form-control" id="smtp_port" name="smtp_port" type="number" min="1" max="65535" required
                           value="{{ old('smtp_port', $settings->smtp_port) }}">
                </div>
                <div class="col-lg-3">
                    <label class="form-label" for="smtp_scheme">Verbindung</label>
                    <select class="form-select" id="smtp_scheme" name="smtp_scheme" required>
                        <option value="smtp" @selected(old('smtp_scheme', $settings->smtp_scheme) === 'smtp')>SMTP / STARTTLS</option>
                        <option value="smtps" @selected(old('smtp_scheme', $settings->smtp_scheme) === 'smtps')>SMTPS</option>
                    </select>
                </div>
                <div class="col-lg-6">
                    <label class="form-label" for="smtp_username">Benutzername</label>
                    <input class="form-control" id="smtp_username" name="smtp_username" maxlength="255"
                           value="{{ old('smtp_username') }}" autocomplete="off">
                    <div class="form-text">
                        Aus Sicherheitsgründen wird der gespeicherte Benutzername nicht vorausgefüllt.
                        Leer lassen, wenn keine Anmeldung erforderlich ist.
                    </div>
                </div>
                <div class="col-lg-6">
                    <label class="form-label" for="smtp_password">Neues Passwort</label>
                    <input class="form-control" id="smtp_password" name="smtp_password" type="password" maxlength="255"
                           autocomplete="new-password">
                    <div class="form-text">Leer lassen, um das vorhandene Passwort beizubehalten.</div>
                </div>
                <div class="col-lg-6">
                    <label class="form-label" for="from_address">Absenderadresse</label>
                    <input class="form-control" id="from_address" name="from_address" type="email" required maxlength="255"
                           value="{{ old('from_address', $settings->from_address) }}">
                </div>
                <div class="col-lg-6">
                    <label class="form-label" for="from_name">Absendername</label>
                    <input class="form-control" id="from_name" name="from_name" required maxlength="255"
                           value="{{ old('from_name', $settings->from_name) }}">
                </div>
            </div>

            <input type="hidden" name="clear_credentials" value="0">
            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" id="clear_credentials" name="clear_credentials" value="1">
                <label class="form-check-label" for="clear_credentials">Gespeicherten Benutzernamen und Passwort entfernen</label>
            </div>

            <x-validation-errors />
        </div>
        <div class="card-footer bg-body border-0 d-flex flex-wrap gap-2">
            <button class="btn btn-primary">SMTP-Einstellungen speichern</button>
        </div>
    </form>

    <form method="POST" action="{{ route('communication-settings.test') }}" class="mt-3"
          onsubmit="return confirm('Testmail an deine eigene Konto-E-Mail-Adresse senden?')">
        @csrf
        <button class="btn btn-outline-primary">Testmail an {{ auth()->user()->email }} senden</button>
    </form>
</div>
@endsection
