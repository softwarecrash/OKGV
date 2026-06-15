@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Globale Konfiguration</h1>
            <p class="text-secondary mb-0">Zentrale Vorgaben für Darstellung, Benutzerverwaltung und E-Mail-Versand.</p>
        </div>
        <a class="btn btn-outline-primary" href="{{ route('permission-profiles.index') }}">Rechtevorlagen</a>
    </div>

    <form method="POST" action="{{ route('application-settings.update') }}" class="card border-0 shadow-sm">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="mb-4">
                <label class="form-label" for="system_name">Systemname</label>
                <input class="form-control @error('system_name') is-invalid @enderror"
                       id="system_name" name="system_name" maxlength="80" required
                       value="{{ old('system_name', $settings->system_name) }}">
                @error('system_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">
                    Dieser Name ersetzt „OKGV“ in Navigation, Seitentitel und automatisch versendeten E-Mails.
                    Beispiel: „KGV Sonnental“.
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label" for="default_board_permission_profile_id">Standardvorlage für neue Vorstandsmitglieder</label>
                <select class="form-select @error('default_board_permission_profile_id') is-invalid @enderror"
                        id="default_board_permission_profile_id"
                        name="default_board_permission_profile_id" required>
                    @foreach ($profiles->where('is_active', true) as $profile)
                        <option value="{{ $profile->id }}"
                                @selected((int) old('default_board_permission_profile_id', $settings->default_board_permission_profile_id) === $profile->id)>
                            {{ $profile->name }}
                        </option>
                    @endforeach
                </select>
                @error('default_board_permission_profile_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">
                    Die Vorlage wird in der Rechteverwaltung bei neuen Vorstandszuweisungen vorausgewählt.
                </div>
            </div>

            @if (App\Enums\FeatureModule::WorkHours->enabled())
            <fieldset>
                <legend class="h5">Arbeitsstunden je Parzelle</legend>
                <p class="text-secondary">
                    Die Pflichtstunden gelten als Jahreswert je Parzelle. Bei unterjähriger Verpachtung wird der Wert automatisch nach belegten Kalendertagen anteilig berechnet. Bereits angelegte Periodenkonten ändern sich durch neue Vorgabewerte nicht rückwirkend.
                </p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="default_work_hours_required">Jährliche Pflichtstunden je Parzelle</label>
                        <div class="input-group">
                            <input class="form-control @error('default_work_hours_required') is-invalid @enderror"
                                   id="default_work_hours_required"
                                   name="default_work_hours_required"
                                   type="number" min="0" step="0.25" required
                                   value="{{ old('default_work_hours_required', $settings->default_work_hours_required) }}">
                            <span class="input-group-text">Std.</span>
                            @error('default_work_hours_required')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="default_work_hour_penalty_rate">Betrag je Fehlstunde</label>
                        <div class="input-group">
                            <input class="form-control @error('default_work_hour_penalty_rate') is-invalid @enderror"
                                   id="default_work_hour_penalty_rate"
                                   name="default_work_hour_penalty_rate"
                                   type="number" min="0" step="0.01" required
                                   value="{{ old('default_work_hour_penalty_rate', $settings->default_work_hour_penalty_rate) }}">
                            <span class="input-group-text">€</span>
                            @error('default_work_hour_penalty_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </fieldset>
            @endif

            <hr class="my-4">
            <h2 class="h5">Aktive Funktionsmodule</h2>
            <p class="text-secondary">
                Diese Auswahl wird durch die Serverkonfiguration gesteuert. Deaktivierte Module und ihre Daten bleiben gespeichert, sind aber in dieser Instanz nicht nutzbar.
            </p>
            <div class="row g-2">
                @foreach ($modules as $module)
                    <div class="col-md-6 col-lg-4">
                        <div class="border rounded p-3 h-100 d-flex justify-content-between align-items-center gap-2">
                            <span>{{ $module->label() }}</span>
                            <span class="badge {{ $module->enabled() ? 'text-bg-success' : 'text-bg-secondary' }}">
                                {{ $module->enabled() ? 'Aktiv' : 'Deaktiviert' }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

        </div>
        <div class="card-footer bg-body border-0">
            <button class="btn btn-primary">Konfiguration speichern</button>
        </div>
    </form>

    <section class="mt-5" id="smtp">
        <h2 class="h4 mb-1">SMTP-Einstellungen</h2>
        <p class="text-secondary mb-3">Mailserver für Kontobestätigungen und Serienmails konfigurieren.</p>

        <div class="alert alert-warning">
            Die Zugangsdaten werden verschlüsselt gespeichert. Das Passwort wird nicht angezeigt
            und bleibt unverändert, wenn das Passwortfeld leer ist.
        </div>

        <form method="POST" action="{{ route('communication-settings.update') }}" class="card border-0 shadow-sm">
            @csrf
            @method('PUT')
            <div class="card-body">
                <input type="hidden" name="smtp_enabled" value="0">
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" id="smtp_enabled" name="smtp_enabled" value="1"
                           @checked(old('smtp_enabled', $communicationSettings->smtp_enabled))>
                    <label class="form-check-label" for="smtp_enabled">SMTP-Versand aktivieren</label>
                </div>

                <div class="row g-3">
                    <div class="col-lg-6">
                        <label class="form-label" for="smtp_host">SMTP-Server</label>
                        <input class="form-control" id="smtp_host" name="smtp_host" required maxlength="255"
                               value="{{ old('smtp_host', $communicationSettings->smtp_host) }}" placeholder="smtp.example.de">
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label" for="smtp_port">Port</label>
                        <input class="form-control" id="smtp_port" name="smtp_port" type="number" min="1" max="65535" required
                               value="{{ old('smtp_port', $communicationSettings->smtp_port) }}">
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label" for="smtp_scheme">Verbindung</label>
                        <select class="form-select" id="smtp_scheme" name="smtp_scheme" required>
                            <option value="smtp" @selected(old('smtp_scheme', $communicationSettings->smtp_scheme) === 'smtp')>SMTP / STARTTLS</option>
                            <option value="smtps" @selected(old('smtp_scheme', $communicationSettings->smtp_scheme) === 'smtps')>SMTPS</option>
                        </select>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label" for="smtp_username">Benutzername</label>
                        <input class="form-control" id="smtp_username" name="smtp_username" maxlength="255"
                               value="{{ old('smtp_username', $communicationSettings->smtp_username) }}" autocomplete="username">
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label" for="smtp_password">Passwort</label>
                        <input class="form-control" id="smtp_password" name="smtp_password" type="password" maxlength="255"
                               autocomplete="new-password">
                        <div class="form-text">Leer lassen, um das vorhandene Passwort beizubehalten.</div>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label" for="from_address">Absenderadresse</label>
                        <input class="form-control" id="from_address" name="from_address" type="email" required maxlength="255"
                               value="{{ old('from_address', $communicationSettings->from_address) }}">
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label" for="from_name">Absendername</label>
                        <input class="form-control" id="from_name" name="from_name" required maxlength="255"
                               value="{{ old('from_name', $communicationSettings->from_name) }}">
                    </div>
                </div>

                <input type="hidden" name="clear_credentials" value="0">
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="clear_credentials" name="clear_credentials" value="1">
                    <label class="form-check-label" for="clear_credentials">Gespeicherten Benutzernamen und Passwort entfernen</label>
                </div>

                <x-validation-errors />
            </div>
            <div class="card-footer bg-body border-0">
                <button class="btn btn-primary">SMTP-Einstellungen speichern</button>
            </div>
        </form>

        <form method="POST" action="{{ route('communication-settings.test') }}" class="card border-0 shadow-sm mt-3"
              onsubmit="return confirm('Testmail an die eingegebene Zieladresse senden?')">
            @csrf
            <div class="card-body">
                <label class="form-label" for="test_email">Zieladresse für Testmail</label>
                <div class="input-group">
                    <input class="form-control @error('test_email') is-invalid @enderror"
                           id="test_email" name="test_email" type="email" required maxlength="255"
                           value="{{ old('test_email', auth()->user()->email) }}"
                           placeholder="empfaenger@example.de">
                    <button class="btn btn-outline-primary">Testmail senden</button>
                    @error('test_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-text">
                    Die Adresse wird nur für diesen Testversand verwendet und nicht als Systemeinstellung gespeichert.
                    Eine erfolgreiche Rückmeldung bestätigt die Annahme durch den SMTP-Server, nicht die endgültige Zustellung beim Empfänger.
                </div>
            </div>
        </form>
    </section>
</div>
@endsection
