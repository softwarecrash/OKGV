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

    <form method="POST" action="{{ route('application-settings.update') }}" enctype="multipart/form-data" class="card border-0 shadow-sm">
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

            <hr class="my-4">
            <fieldset>
                <legend class="h5">Vereinsstammdaten</legend>
                <p class="text-secondary">
                    Diese Angaben erscheinen in Briefen, Rechnungen und weiteren Vereinsdokumenten. Verwende den rechtlich korrekten Vereinsnamen und eine erreichbare Kontaktadresse.
                </p>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="association_name">Vereinsname</label>
                        <input class="form-control @error('association_name') is-invalid @enderror"
                               id="association_name" name="association_name" maxlength="150" required
                               value="{{ old('association_name', $settings->association_name) }}"
                               placeholder="Kleingartenverein Sonnental e. V.">
                        @error('association_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Offizieller Name für Rechnungen, Schreiben und Absenderangaben.</div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="street">Straße und Hausnummer</label>
                        <input class="form-control @error('street') is-invalid @enderror"
                               id="street" name="street" maxlength="255" required
                               value="{{ old('street', $settings->street) }}">
                        @error('street')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="zip">Postleitzahl</label>
                        <input class="form-control @error('zip') is-invalid @enderror"
                               id="zip" name="zip" maxlength="20" required
                               value="{{ old('zip', $settings->zip) }}">
                        @error('zip')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="city">Ort</label>
                        <input class="form-control @error('city') is-invalid @enderror"
                               id="city" name="city" maxlength="150" required
                               value="{{ old('city', $settings->city) }}">
                        @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="contact_name">Ansprechpartner</label>
                        <input class="form-control @error('contact_name') is-invalid @enderror"
                               id="contact_name" name="contact_name" maxlength="150" required
                               value="{{ old('contact_name', $settings->contact_name) }}"
                               placeholder="Vorstand">
                        @error('contact_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="phone">Telefon</label>
                        <input class="form-control @error('phone') is-invalid @enderror"
                               id="phone" name="phone" maxlength="50"
                               value="{{ old('phone', $settings->phone) }}">
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Vereins-E-Mail-Adresse</label>
                        <input class="form-control @error('email') is-invalid @enderror"
                               id="email" name="email" type="email" maxlength="255" required
                               value="{{ old('email', $settings->email) }}">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="website">Vereinswebseite (optional)</label>
                        <input class="form-control @error('website') is-invalid @enderror"
                               id="website" name="website" type="url" maxlength="255"
                               value="{{ old('website', $settings->website) }}"
                               placeholder="https://www.example.de">
                        @error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="document_footer">Dokumentfußzeile (optional)</label>
                        <textarea class="form-control @error('document_footer') is-invalid @enderror"
                                  id="document_footer" name="document_footer" rows="2"
                                  maxlength="1000" placeholder="Vereinsregister, Kontakt oder weitere Pflichtangaben">{{ old('document_footer', $settings->document_footer) }}</textarea>
                        @error('document_footer')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Erscheint in Rechnungen, Briefen, Zahlungserinnerungen und Mahnungen. Ohne Eingabe erzeugt OKGV eine Fußzeile aus den Vereinskontaktdaten.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="email_signature">E-Mail-Signatur (optional)</label>
                        <textarea class="form-control @error('email_signature') is-invalid @enderror"
                                  id="email_signature" name="email_signature" rows="4"
                                  maxlength="2000" placeholder="Mit freundlichen Grüßen&#10;Der Vorstand">{{ old('email_signature', $settings->email_signature) }}</textarea>
                        @error('email_signature')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Wird unter Serienmails gesetzt. Reiner Text wird sicher dargestellt; HTML-Code wird nicht ausgeführt.</div>
                    </div>
                </div>
            </fieldset>

            <hr class="my-4">
            <fieldset>
                <legend class="h5">Vereinslogo</legend>
                <p class="text-secondary">JPEG, PNG oder WebP bis 2 MiB. Das Logo wird geprüft und im privaten Anwendungsbereich gespeichert.</p>
                @if ($settings->logo_path)
                    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                        <img src="{{ route('association-logo.show', ['v' => $settings->updated_at?->timestamp]) }}"
                             alt="Aktuelles Vereinslogo" style="max-height: 90px; max-width: 240px;">
                        <span class="text-secondary">{{ $settings->logo_original_name }}</span>
                    </div>
                @endif
                <input class="form-control @error('logo') is-invalid @enderror"
                       id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp">
                @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <input type="hidden" name="remove_logo" value="0">
                @if ($settings->logo_path)
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="remove_logo" name="remove_logo" value="1">
                        <label class="form-check-label" for="remove_logo">Vorhandenes Logo entfernen</label>
                    </div>
                @endif
            </fieldset>

            <hr class="my-4">
            <fieldset>
                <legend class="h5">Bankverbindung für Rechnungen</legend>
                <p class="text-secondary">
                    Diese Bankverbindung wird als Überweisungsziel auf Vereinsdokumenten verwendet. SEPA-Gläubiger-ID und Lastschriftkonto bleiben separat in den SEPA-Einstellungen. IBAN und BIC werden verschlüsselt gespeichert.
                </p>
                @if ($settings->masked_bank_iban)
                    <div class="alert alert-info">Gespeicherte IBAN: <strong>{{ $settings->masked_bank_iban }}</strong>. Ein leeres IBAN-Feld behält diesen Wert bei.</div>
                @endif
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="bank_account_holder">Kontoinhaber</label>
                        <input class="form-control @error('bank_account_holder') is-invalid @enderror"
                               id="bank_account_holder" name="bank_account_holder" maxlength="150"
                               value="{{ old('bank_account_holder', $settings->bank_account_holder) }}">
                        @error('bank_account_holder')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="bank_name">Kreditinstitut</label>
                        <input class="form-control @error('bank_name') is-invalid @enderror"
                               id="bank_name" name="bank_name" maxlength="150"
                               value="{{ old('bank_name', $settings->bank_name) }}">
                        @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="bank_iban">IBAN</label>
                        <input class="form-control text-uppercase @error('bank_iban') is-invalid @enderror"
                               id="bank_iban" name="bank_iban" maxlength="34" autocomplete="off"
                               value="{{ old('bank_iban') }}" placeholder="{{ $settings->masked_bank_iban ?? 'DE...' }}">
                        @error('bank_iban')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Leerzeichen sind erlaubt und werden automatisch entfernt.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="bank_bic">BIC</label>
                        <input class="form-control text-uppercase @error('bank_bic') is-invalid @enderror"
                               id="bank_bic" name="bank_bic" maxlength="11" autocomplete="off"
                               value="{{ old('bank_bic') }}">
                        @error('bank_bic')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if ($settings->bank_bic)<div class="form-text">Eine BIC ist gespeichert. Leer lassen, um sie beizubehalten.</div>@endif
                    </div>
                </div>
                <input type="hidden" name="clear_bank_details" value="0">
                @if ($settings->bank_iban || $settings->bank_account_holder || $settings->bank_name)
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="clear_bank_details" name="clear_bank_details" value="1">
                        <label class="form-check-label" for="clear_bank_details">Gesamte Rechnungs-Bankverbindung entfernen</label>
                    </div>
                @endif
            </fieldset>

            <hr class="my-4">
            <fieldset>
                <legend class="h5">Standardwerte</legend>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="default_payment_term_days">Standard-Zahlungsziel</label>
                        <div class="input-group">
                            <input class="form-control @error('default_payment_term_days') is-invalid @enderror"
                                   id="default_payment_term_days" name="default_payment_term_days"
                                   type="number" min="1" max="365" required
                                   value="{{ old('default_payment_term_days', $settings->default_payment_term_days ?? 14) }}">
                            <span class="input-group-text">Tage</span>
                            @error('default_payment_term_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-text">Wird bei neuen Abrechnungsperioden als Fälligkeit nach dem Periodenende vorgeschlagen.</div>
                    </div>
                </div>
            </fieldset>

            <hr class="my-4">
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

        @if (config('demo.enabled'))
            <div class="alert alert-info">
                Diese Installation läuft im Demo-Modus. SMTP-Einstellungen und Testmails sind gesperrt,
                damit öffentlich zugängliche Testkonten keinen Mailversand auslösen können.
            </div>
        @else
            <div class="alert alert-warning">
                Die Zugangsdaten werden verschlüsselt gespeichert. Das Passwort wird nicht angezeigt
                und bleibt unverändert, wenn das Passwortfeld leer ist.
            </div>
        @endif

        <form method="POST" action="{{ route('communication-settings.update') }}" class="card border-0 shadow-sm">
            @csrf
            @method('PUT')
            <div class="card-body">
                <input type="hidden" name="smtp_enabled" value="0">
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" id="smtp_enabled" name="smtp_enabled" value="1"
                           @checked(old('smtp_enabled', $communicationSettings->smtp_enabled))
                           @disabled(config('demo.enabled'))>
                    <label class="form-check-label" for="smtp_enabled">SMTP-Versand aktivieren</label>
                </div>

                <div class="row g-3">
                    <div class="col-lg-6">
                        <label class="form-label" for="smtp_host">SMTP-Server</label>
                        <input class="form-control" id="smtp_host" name="smtp_host" required maxlength="255"
                               value="{{ old('smtp_host', $communicationSettings->smtp_host) }}" placeholder="smtp.example.de"
                               @disabled(config('demo.enabled'))>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label" for="smtp_port">Port</label>
                        <input class="form-control" id="smtp_port" name="smtp_port" type="number" min="1" max="65535" required
                               value="{{ old('smtp_port', $communicationSettings->smtp_port) }}"
                               @disabled(config('demo.enabled'))>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label" for="smtp_scheme">Verbindung</label>
                        <select class="form-select" id="smtp_scheme" name="smtp_scheme" required
                                @disabled(config('demo.enabled'))>
                            <option value="smtp" @selected(old('smtp_scheme', $communicationSettings->smtp_scheme) === 'smtp')>SMTP / STARTTLS</option>
                            <option value="smtps" @selected(old('smtp_scheme', $communicationSettings->smtp_scheme) === 'smtps')>SMTPS</option>
                            <option value="none" @selected(old('smtp_scheme', $communicationSettings->smtp_scheme) === 'none')>SMTP ohne Verschlüsselung</option>
                        </select>
                        <div class="form-text">Für lokale Relays wie <code>localhost:25</code> ohne TLS-Zertifikat.</div>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label" for="smtp_username">Benutzername</label>
                        <input class="form-control" id="smtp_username" name="smtp_username" maxlength="255"
                               value="{{ old('smtp_username', $communicationSettings->smtp_username) }}" autocomplete="username"
                               @disabled(config('demo.enabled'))>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label" for="smtp_password">Passwort</label>
                        <input class="form-control" id="smtp_password" name="smtp_password" type="password" maxlength="255"
                               autocomplete="new-password"
                               @disabled(config('demo.enabled'))>
                        <div class="form-text">Leer lassen, um das vorhandene Passwort beizubehalten.</div>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label" for="from_address">Absenderadresse</label>
                        <input class="form-control" id="from_address" name="from_address" type="email" required maxlength="255"
                               value="{{ old('from_address', $communicationSettings->from_address) }}"
                               @disabled(config('demo.enabled'))>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label" for="from_name">Absendername</label>
                        <input class="form-control" id="from_name" name="from_name" required maxlength="255"
                               value="{{ old('from_name', $communicationSettings->from_name) }}"
                               @disabled(config('demo.enabled'))>
                    </div>
                </div>

                <input type="hidden" name="clear_credentials" value="0">
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="clear_credentials" name="clear_credentials" value="1"
                           @disabled(config('demo.enabled'))>
                    <label class="form-check-label" for="clear_credentials">Gespeicherten Benutzernamen und Passwort entfernen</label>
                </div>

                <x-validation-errors />
            </div>
            <div class="card-footer bg-body border-0">
                <button class="btn btn-primary" @disabled(config('demo.enabled'))>SMTP-Einstellungen speichern</button>
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
                           placeholder="empfaenger@example.de"
                           @disabled(config('demo.enabled'))>
                    <button class="btn btn-outline-primary" @disabled(config('demo.enabled'))>Testmail senden</button>
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
