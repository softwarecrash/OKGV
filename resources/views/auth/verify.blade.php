@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header">E-Mail-Adresse bestätigen</div>

                <div class="card-body">
                    @if (session('resent'))
                        <div class="alert alert-success" role="alert">
                            Ein neuer Bestätigungslink wurde an deine E-Mail-Adresse gesendet.
                        </div>
                    @endif

                    <p>
                        Dein Konto wurde freigegeben. Vor dem ersten Zugriff musst du noch deine E-Mail-Adresse bestätigen.
                        Prüfe bitte auch den Spam-Ordner.
                    </p>
                    <p class="mb-0">
                    Falls keine Nachricht angekommen ist,
                    <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                        @csrf
                        <button type="submit" class="btn btn-link p-0 m-0 align-baseline">neuen Bestätigungslink senden</button>.
                    </form>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
