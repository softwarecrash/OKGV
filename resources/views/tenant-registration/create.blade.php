@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">Zugang beantragen</div>
                <div class="card-body">
                    <p class="text-secondary">
                        Gib deine Daten so ein, wie sie dem Verein bekannt sind. Der Vorstand oder ein Administrator prüft anschließend die Anfrage. Erst danach kannst du dich anmelden.
                    </p>
                    <form method="POST" action="{{ route('tenant-registration.store') }}">
                        @csrf
                        <x-validation-errors />
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="first_name">Vorname</label>
                                <input class="form-control" id="first_name" name="first_name" maxlength="255" value="{{ old('first_name') }}" required autocomplete="given-name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="last_name">Nachname</label>
                                <input class="form-control" id="last_name" name="last_name" maxlength="255" value="{{ old('last_name') }}" required autocomplete="family-name">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label" for="email">E-Mail-Adresse</label>
                                <input class="form-control" type="email" id="email" name="email" maxlength="255" value="{{ old('email') }}" required autocomplete="email">
                                <div class="form-text">Mit dieser Adresse meldest du dich nach der Freigabe an.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="parcel_number">Parzellennummer <span class="text-secondary">(optional)</span></label>
                                <input class="form-control" id="parcel_number" name="parcel_number" maxlength="255" value="{{ old('parcel_number') }}">
                                <div class="form-text">Wenn du Pächter bist, genau wie auf deinem Pachtvertrag angegeben. Für Vorstand, Helfer oder technische Konten leer lassen.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password">Passwort</label>
                                <input class="form-control" type="password" id="password" name="password" required autocomplete="new-password">
                                <div class="form-text">Mindestens 12 Zeichen sowie Buchstaben und Zahlen.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password_confirmation">Passwort wiederholen</label>
                                <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                            </div>
                        </div>
                        <div class="alert alert-info mt-4 mb-3">
                            Die Anfrage legt ein Benutzerkonto an, das erst nach E-Mail-Bestätigung und Freigabe durch den Verein nutzbar wird. Eine Parzellenzuordnung kann direkt über die angegebene Parzellennummer oder später in der Verwaltung ergänzt werden.
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary">Anfrage absenden</button>
                            <a class="btn btn-outline-secondary" href="{{ route('login') }}">Zur Anmeldung</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
