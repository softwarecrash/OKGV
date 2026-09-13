<x-validation-errors />

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h4">Benutzerkonto</h2>
        @if (! $member->user)
            <div class="alert alert-info mb-0">
                Diesem Mitglied ist noch kein Benutzerkonto zugeordnet. Verknüpfe ein bestehendes Konto in den Stammdaten oder lasse das Mitglied eine Registrierung durchführen.
            </div>
        @else
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
        @endif
    </div>
</div>
