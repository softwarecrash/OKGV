@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <h1 class="h2 mb-3">Datenschutzinformationen</h1>
            <p>{{ config('app.name', 'OKGV') }} wird von dem Verein betrieben, der diese Installation nutzt. Der Verein ist für die Verarbeitung der in dieser Instanz gespeicherten personenbezogenen Daten verantwortlich.</p>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h5">Verarbeitete Daten</h2>
                    <p class="mb-0">Je nach Nutzung werden Mitglieds- und Kontaktdaten, Parzellenzuordnungen, Zählerstände, Rechnungen, Zahlungen, Dokumente, Arbeitsstunden, Kommunikation und sicherheitsrelevante Auditereignisse verarbeitet.</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h5">Zwecke und Aufbewahrung</h2>
                    <p class="mb-0">Die Daten dienen der Mitglieder-, Vertrags- und Vereinsverwaltung. Rechnungs-, Zahlungs- und Vertragsnachweise können gesetzlichen oder vertraglichen Aufbewahrungspflichten unterliegen. Eine Löschanfrage wird deshalb geprüft und kann bis zum Ablauf solcher Pflichten zurückgestellt werden.</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h5">Deine Rechte</h2>
                    <p>Du kannst Auskunft, Berichtigung, Einschränkung, Datenübertragung und Löschung verlangen sowie freiwillige Freigaben jederzeit widerrufen. Im angemeldeten Datenschutzbereich steht eine maschinenlesbare Datenauskunft bereit.</p>
                    <p class="mb-0">Richte weitergehende Anliegen an den Vorstand oder die vom Verein benannte Datenschutzkontaktstelle. Außerdem besteht ein Beschwerderecht bei der zuständigen Datenschutzaufsichtsbehörde.</p>
                </div>
            </div>

            <a class="btn btn-outline-primary" href="{{ auth()->check() ? route('privacy.index') : route('login') }}">
                {{ auth()->check() ? 'Zum Datenschutzbereich' : 'Anmelden' }}
            </a>
        </div>
    </div>
</div>
@endsection
