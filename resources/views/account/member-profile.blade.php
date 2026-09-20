@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header"><h1 class="h4 mb-0">Meine Daten</h1></div>
                <div class="card-body">
                    <p class="text-secondary">Halte deine Kontaktdaten aktuell. Mitgliedsnummer, Mitgliedsstatus, Parzellen und Rechte werden durch den Verein verwaltet.</p>

                    @include('components.validation-errors')

                    <form method="POST" action="{{ route('account.member-profile.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="first_name">Vorname</label>
                                <input class="form-control" id="first_name" name="first_name" value="{{ old('first_name', $member->first_name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="last_name">Nachname</label>
                                <input class="form-control" id="last_name" name="last_name" value="{{ old('last_name', $member->last_name) }}" required>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label" for="street">Straße und Hausnummer</label>
                                <input class="form-control" id="street" name="street" value="{{ old('street', $member->street) }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="zip">PLZ</label>
                                <input class="form-control" id="zip" name="zip" value="{{ old('zip', $member->zip) }}" maxlength="10" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="city">Ort</label>
                                <input class="form-control" id="city" name="city" value="{{ old('city', $member->city) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="phone">Telefon</label>
                                <input class="form-control" id="phone" name="phone" value="{{ old('phone', $member->phone) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="mobile">Mobil</label>
                                <input class="form-control" id="mobile" name="mobile" value="{{ old('mobile', $member->mobile) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="email">Kontakt-E-Mail</label>
                                <input class="form-control" id="email" name="email" type="email" value="{{ old('email', $member->email) }}">
                                <div class="form-text">Login-E-Mail: {{ $user->email }}. Änderungen der Login-Adresse erfolgen über den Vorstand.</div>
                            </div>
                        </div>

                        <button class="btn btn-primary mt-4" type="submit">Kontaktdaten speichern</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
