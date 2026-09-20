@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header"><h1 class="h4 mb-0">Meine Daten</h1></div>
                <div class="card-body">
                    <p class="text-secondary">Halte deine Kontaktdaten aktuell. Mitgliedsnummer, Mitgliedsstatus, Parzellen und Rechte werden durch den Verein verwaltet.</p>

                    <dl class="row mb-4">
                        <dt class="col-sm-4">Name</dt><dd class="col-sm-8">{{ $member->full_name }}</dd>
                        <dt class="col-sm-4">Anschrift</dt><dd class="col-sm-8">{{ $member->street }}<br>{{ $member->zip }} {{ $member->city }}</dd>
                        <dt class="col-sm-4">Telefon</dt><dd class="col-sm-8">{{ $member->phone ?: 'Nicht hinterlegt' }}</dd>
                        <dt class="col-sm-4">Mobil</dt><dd class="col-sm-8">{{ $member->mobile ?: 'Nicht hinterlegt' }}</dd>
                        <dt class="col-sm-4">Kontakt-E-Mail</dt><dd class="col-sm-8">{{ $member->email ?: 'Nicht hinterlegt' }}</dd>
                        <dt class="col-sm-4">Login-E-Mail</dt><dd class="col-sm-8">{{ $user->email }}</dd>
                    </dl>

                    <a class="btn btn-primary" href="{{ route('account.member-profile.edit') }}">Daten bearbeiten</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
