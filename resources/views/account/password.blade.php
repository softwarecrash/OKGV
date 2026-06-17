@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h1 class="h4 mb-0">Passwort ändern</h1>
                </div>
                <div class="card-body">
                    <p class="text-secondary">
                        Ändere hier dein eigenes Passwort. Verwende ein langes Passwort, das du nicht bei anderen Diensten nutzt.
                    </p>

                    @include('components.validation-errors')

                    <form method="POST" action="{{ route('account.password.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label" for="current_password">Aktuelles Passwort</label>
                            <input class="form-control @error('current_password') is-invalid @enderror"
                                   id="current_password"
                                   name="current_password"
                                   type="password"
                                   autocomplete="current-password"
                                   required>
                            @error('current_password')
                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="password">Neues Passwort</label>
                            <input class="form-control @error('password') is-invalid @enderror"
                                   id="password"
                                   name="password"
                                   type="password"
                                   autocomplete="new-password"
                                   required>
                            <div class="form-text">Mindestens 8 Zeichen, besser länger und eindeutig.</div>
                            @error('password')
                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="password_confirmation">Neues Passwort wiederholen</label>
                            <input class="form-control"
                                   id="password_confirmation"
                                   name="password_confirmation"
                                   type="password"
                                   autocomplete="new-password"
                                   required>
                        </div>

                        <button class="btn btn-primary" type="submit">Passwort speichern</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
