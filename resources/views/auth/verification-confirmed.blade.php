@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-success text-white">E-Mail-Adresse bestätigt</div>
                <div class="card-body">
                    <h1 class="h4">Vielen Dank.</h1>
                    @if ($alreadyVerified)
                        <p>Diese E-Mail-Adresse war bereits bestätigt.</p>
                    @else
                        <p>Deine E-Mail-Adresse wurde erfolgreich bestätigt.</p>
                    @endif

                    @if ($awaitingApproval)
                        <p class="text-secondary">Deine Registrierung wird jetzt noch vom Verein geprüft. Nach der Freigabe kannst du dich mit deiner E-Mail-Adresse und deinem Passwort anmelden.</p>
                    @else
                        <p class="text-secondary">Du kannst dich jetzt mit deiner E-Mail-Adresse und deinem Passwort anmelden.</p>
                    @endif

                    <a class="btn btn-primary" href="{{ route('login') }}">Zur Anmeldung</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
