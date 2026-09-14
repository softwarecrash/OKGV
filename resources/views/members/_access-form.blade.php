<x-validation-errors />

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h4">Benutzerkonto</h2>
        @if (! $member->user)
            <div class="alert alert-info mb-0">
                Diesem Mitglied ist noch kein Benutzerkonto zugeordnet. Verknüpfe ein bestehendes Konto in den Stammdaten oder lasse das Mitglied eine Registrierung durchführen.
            </div>
        @else
            @if ($canUpdateUserAccount)
                <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span>Falsch oder doppelt angelegte Zugänge ohne Mitgliedszuordnung verwaltest du separat.</span>
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('user-accounts.index') }}">Nicht zugeordnete Konten</a>
                </div>
            @endif
            <dl class="row mb-4">
                <dt class="col-sm-3">Login-E-Mail</dt><dd class="col-sm-9">{{ $member->user->email }}</dd>
                <dt class="col-sm-3">E-Mail-Status</dt><dd class="col-sm-9">{{ $member->user->hasVerifiedEmail() ? 'Bestätigt' : 'Noch nicht bestätigt' }}</dd>
                <dt class="col-sm-3">Aktuelle Rolle</dt>
                <dd class="col-sm-9">
                    {{ $member->user->role->label() }}
                    @if ($member->user->isAdministrator())
                        · Technischer Administrator
                    @endif
                </dd>
            </dl>

            @if ($member->user->isAdministrator() && $administratorCount <= 1)
                <div class="alert alert-warning mb-0">Dieses Konto ist der letzte technische Administrator. Lege zuerst einen weiteren technischen Administrator an, bevor du dessen Rechte änderst.</div>
            @elseif (! $canUpdateUserAccess)
                <div class="alert alert-info mb-0">Dieses Konto kann mit deinen aktuellen Rechten nicht verändert werden.</div>
            @else
                <form method="POST" action="{{ route('user-permissions.update', $member->user) }}" onsubmit="return confirm('Rolle und Rechte für {{ addslashes($member->full_name) }} wirklich speichern? Der Zugriff ändert sich sofort.')">
                    @csrf
                    @method('PUT')
                    <fieldset @disabled(config('demo.enabled'))>
                    <div class="row g-3">
                        <div class="col-lg-5">
                            <label class="form-label" for="role">Rolle</label>
                            <select class="form-select" id="role" name="role" required>
                                @foreach ($assignableRoles as $role)
                                    <option value="{{ $role->value }}" @selected($member->user->role === $role)>{{ $role->label() }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Vorstände können Pächter und Vorstände verwalten. Administratoren können weitere Vereinsrollen und technische Administratoren festlegen.</div>
                        </div>
                        @if ($canManagePermissionDetails)
                            <div class="col-lg-7">
                                <label class="form-label" for="permission_profile_id">Rechtevorlage</label>
                                <select class="form-select" id="permission_profile_id" name="permission_profile_id">
                                    <option value="">Individuelle Auswahl verwenden</option>
                                    @foreach ($profiles as $profile)
                                        <option value="{{ $profile->id }}" @selected($member->user->permission_profile_id === $profile->id) @selected($member->user->role === App\Enums\UserRole::Tenant && $tenantProfileId === $profile->id)>{{ $profile->name }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Pächter erhalten beim Speichern automatisch die feste Vorlage „Pächter Standard“ ohne Verwaltungsrechte.</div>
                            </div>
                        @else
                            <div class="col-lg-7"><div class="alert alert-info mb-0">Neue Vorstandsmitglieder erhalten automatisch die globale Standard-Rechtevorlage. Einzelrechte können nur durch Administratoren angepasst werden.</div></div>
                        @endif
                    </div>

                    @if ($canManagePermissionDetails)
                        <div class="form-check form-switch mt-4">
                            <input type="hidden" name="is_system_admin" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_system_admin" name="is_system_admin" value="1" @checked($member->user->isAdministrator())>
                            <label class="form-check-label" for="is_system_admin"><strong>Technischer Administrator</strong></label>
                            <div class="form-text">Erlaubt technische Verwaltung, aber keine zusätzliche Mitgliedschaft oder Parzellenzuordnung.</div>
                        </div>
                        <fieldset class="mt-4">
                            <legend class="h5">Individuelle Rechte</legend>
                            <div class="row g-3">
                                @foreach ($permissions as $permission)
                                    <div class="col-lg-6"><div class="form-check border rounded p-3 ps-5 h-100">
                                        <input class="form-check-input" type="checkbox" id="permission-{{ $permission->value }}" name="permissions[]" value="{{ $permission->value }}" @checked(in_array($permission->value, $member->user->permissions ?? ($member->user->role === App\Enums\UserRole::Board ? $member->user->role->defaultPermissions() : []), true))>
                                        <label class="form-check-label" for="permission-{{ $permission->value }}"><strong>{{ $permission->label() }}</strong><br><span class="text-secondary">{{ $permission->description() }}</span></label>
                                    </div></div>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif
                        <button class="btn btn-primary mt-4">Zugang und Rechte speichern</button>
                    </fieldset>
                    @if (config('demo.enabled'))
                        <p class="form-text mb-0 mt-3">Im Demo-Modus können Zugang und Rechte nicht verändert werden.</p>
                    @endif
                </form>
            @endif

            @if ($canUpdateUserAccount)
                <div class="border-top mt-4 pt-4">
                    <h3 class="h5">Login-E-Mail ändern</h3>
                    <p class="text-secondary">Diese Adresse wird nur für die Anmeldung und Sicherheitsmails verwendet. Die Kontakt-E-Mail in den Stammdaten bleibt unverändert. Nach der Änderung erhält die neue Adresse eine Bestätigungsmail.</p>
                    @if (config('demo.enabled'))
                        <p class="form-text">Im Demo-Modus kann die Login-E-Mail nicht geändert werden.</p>
                    @endif
                    <form method="POST" action="{{ route('user-accounts.email.update', $member->user) }}">
                        @csrf
                        @method('PUT')
                        <fieldset @disabled(config('demo.enabled'))>
                            <label class="form-label" for="login_email">Login-E-Mail</label>
                            <div class="row g-2 align-items-end">
                                <div class="col-lg-7">
                                    <input class="form-control" id="login_email" name="email" type="email" value="{{ old('email', $member->user->email) }}" required autocomplete="email">
                                </div>
                                <div class="col-lg-auto">
                                    <button class="btn btn-outline-primary" type="submit">Login-E-Mail ändern</button>
                                </div>
                            </div>
                        </fieldset>
                    </form>
                </div>
            @endif

            @if ($canDeleteUserAccount)
                <div class="border border-danger rounded p-3 mt-4">
                    <h3 class="h5 text-danger">Benutzerkonto entfernen</h3>
                    <p class="text-secondary small">Entfernt nur den Zugang und hebt die Verknüpfung zum Mitglied auf. Mitglied, Parzellen, Rechnungen und Vereinsdaten bleiben erhalten. Konten mit eigener Fachhistorie können nicht entfernt werden.</p>
                    @if (config('demo.enabled'))
                        <p class="form-text">Im Demo-Modus können Benutzerkonten nicht entfernt werden.</p>
                    @endif
                    <form method="POST" action="{{ route('user-accounts.destroy', $member->user) }}" onsubmit="return confirm('Das Benutzerkonto wirklich entfernen? Die Person kann sich danach nicht mehr anmelden.')">
                        @csrf
                        @method('DELETE')
                        <fieldset @disabled(config('demo.enabled'))>
                            <div class="row g-3">
                                <div class="col-lg-5">
                                    <label class="form-label" for="account_current_password">Dein aktuelles Administratorpasswort</label>
                                    <input class="form-control" id="account_current_password" name="current_password" type="password" required autocomplete="current-password">
                                </div>
                                <div class="col-lg-4">
                                    <label class="form-label" for="account_confirmation">Zur Bestätigung KONTO LÖSCHEN eingeben</label>
                                    <input class="form-control" id="account_confirmation" name="confirmation" required autocomplete="off">
                                </div>
                                <div class="col-lg-auto d-flex align-items-end">
                                    <button class="btn btn-outline-danger" type="submit">Benutzerkonto entfernen</button>
                                </div>
                            </div>
                        </fieldset>
                    </form>
                </div>
            @endif
        @endif
    </div>
</div>
