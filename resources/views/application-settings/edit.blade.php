@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Globale Konfiguration</h1>
            <p class="text-secondary mb-0">Zentrale Vorgaben für Darstellung und Benutzerverwaltung.</p>
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

            <div>
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

            <div class="alert alert-info mt-4 mb-0">
                SMTP-Zugangsdaten bleiben bis zur Kommunikationsphase sicher in der <code>.env</code>.
                E-Mail-Bestätigungen verwenden bereits den dort konfigurierten Laravel-Mailer.
            </div>
        </div>
        <div class="card-footer bg-body border-0">
            <button class="btn btn-primary">Konfiguration speichern</button>
        </div>
    </form>
</div>
@endsection
