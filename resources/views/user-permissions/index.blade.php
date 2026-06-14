@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Rollen und Rechte</h1>
            <p class="text-secondary mb-0">
                Weise Konten eine Vereinsrolle zu. Vorstandsmitglieder erhalten nur die ausdrücklich ausgewählten Rechte.
            </p>
        </div>
        <a class="btn btn-outline-primary" href="{{ route('permission-profiles.index') }}">Rechtevorlagen verwalten</a>
    </div>

    <div class="alert alert-warning">
        <strong>Sensible Rechte bewusst vergeben:</strong>
        SEPA enthält Bankdaten, Abrechnung erlaubt verbindliche Rechnungsfreigaben.
        Änderungen werden mit ausführendem Konto und Zeitpunkt protokolliert.
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Die Änderung konnte nicht gespeichert werden.</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="accordion" id="userAccessAccordion">
        @foreach ($users as $user)
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#user-access-{{ $user->id }}">
                        <span>
                            <strong>{{ $user->name }}</strong>
                            <span class="text-secondary ms-2">{{ $user->email }}</span>
                            <span class="badge text-bg-secondary ms-2">{{ $user->role->label() }}</span>
                        </span>
                    </button>
                </h2>
                <div id="user-access-{{ $user->id }}" class="accordion-collapse collapse" data-bs-parent="#userAccessAccordion">
                    <div class="accordion-body">
                        @if ($user->isAdministrator())
                            <div class="alert alert-info mb-0">
                                Administratorkonten besitzen vollständigen Systemzugriff und können hier nicht herabgestuft werden.
                            </div>
                        @elseif (auth()->user()->is($user))
                            <div class="alert alert-info mb-0">
                                Das eigene Konto kann hier aus Sicherheitsgründen nicht verändert werden.
                            </div>
                        @else
                            <form method="POST" action="{{ route('user-permissions.update', $user) }}"
                                  onsubmit="return confirm('Rolle und Rechte für {{ addslashes($user->name) }} wirklich speichern? Der Zugriff ändert sich sofort.')">
                                @csrf
                                @method('PUT')

                                <div class="row g-3">
                                    <div class="col-lg-5">
                                        <label class="form-label" for="role-{{ $user->id }}">Rolle</label>
                                        <select class="form-select" id="role-{{ $user->id }}" name="role" required>
                                            @foreach ($assignableRoles as $role)
                                                <option value="{{ $role->value }}" @selected($user->role === $role)>{{ $role->label() }}</option>
                                            @endforeach
                                        </select>
                                        <div class="form-text">
                                            Wähle „Vorstand“, um ein freigegebenes Pächterkonto zum Vorstandsmitglied hochzustufen.
                                        </div>
                                    </div>
                                    <div class="col-lg-7">
                                        <label class="form-label" for="profile-{{ $user->id }}">Rechtevorlage für Vorstand</label>
                                        <select class="form-select" id="profile-{{ $user->id }}" name="permission_profile_id">
                                            <option value="">Individuelle Auswahl verwenden</option>
                                            @foreach ($profiles as $profile)
                                                <option value="{{ $profile->id }}"
                                                        @selected($user->permission_profile_id === $profile->id)
                                                        @selected($user->role === App\Enums\UserRole::Tenant && $defaultProfileId === $profile->id)>
                                                    {{ $profile->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="form-text">
                                            Bei ausgewählter Vorlage werden deren aktuelle Rechte als Snapshot übernommen.
                                            Spätere Vorlagenänderungen verändern bestehende Konten nicht automatisch.
                                        </div>
                                    </div>
                                </div>

                                <fieldset class="mt-4">
                                    <legend class="h5">Individuelle Rechte</legend>
                                    <p class="text-secondary">
                                        Diese Auswahl wird verwendet, wenn keine Vorlage gewählt ist. Für andere Rollen gelten die sicheren Rollenvorgaben.
                                    </p>
                                    <div class="row g-3">
                                        @foreach ($permissions as $permission)
                                            <div class="col-lg-6">
                                                <div class="form-check border rounded p-3 ps-5 h-100">
                                                    <input class="form-check-input" type="checkbox"
                                                           id="permission-{{ $user->id }}-{{ $permission->value }}"
                                                           name="permissions[]" value="{{ $permission->value }}"
                                                           @checked(in_array(
                                                               $permission->value,
                                                               $user->permissions ?? ($user->role === App\Enums\UserRole::Board ? $user->role->defaultPermissions() : []),
                                                               true,
                                                           ))>
                                                    <label class="form-check-label" for="permission-{{ $user->id }}-{{ $permission->value }}">
                                                        <strong>{{ $permission->label() }}</strong><br>
                                                        <span class="text-secondary">{{ $permission->description() }}</span>
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </fieldset>

                                <button class="btn btn-primary mt-4">Rolle und Rechte speichern</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
