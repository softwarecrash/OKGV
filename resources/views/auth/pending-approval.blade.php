@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">Konto wartet auf Freigabe</div>
                <div class="card-body">
                    <h1 class="h4">Deine Anmeldung hat funktioniert.</h1>
                    <p class="text-secondary">
                        Dein Benutzerkonto ist angelegt und deine E-Mail-Adresse ist bestätigt. Der Verein muss den Zugang jetzt noch freigeben.
                    </p>
                    <div class="alert alert-info mb-4">
                        Sobald ein Administrator oder Vorstandsmitglied die Anfrage freigegeben hat, kannst du das System normal nutzen. Du musst dich danach nur erneut anmelden oder diese Seite aktualisieren.
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="btn btn-outline-secondary">Abmelden</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
