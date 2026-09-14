@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Nicht zugeordnete Benutzerkonten</h1>
            <p class="text-secondary mb-0">Diese Zugänge sind keinem Mitglied zugeordnet, etwa nach einer korrigierten Registrierung. Sie können hier berichtigt oder entfernt werden.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('members.index') }}">Zur Mitgliederübersicht</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Die Änderung konnte nicht gespeichert werden.</strong>
            <ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @forelse ($users as $user)
                <article class="border rounded p-3 mb-3">
                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                        <div>
                            <strong>{{ $user->name }}</strong>
                            <span class="text-secondary">{{ $user->email }}</span>
                            @if (! $user->hasVerifiedEmail())<span class="badge text-bg-warning ms-2">E-Mail unbestätigt</span>@endif
                        </div>
                        <span class="badge text-bg-secondary align-self-start">{{ $user->role->label() }}</span>
                    </div>
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-7">
                            <form method="POST" action="{{ route('user-accounts.email.update', $user) }}">
                                @csrf
                                @method('PUT')
                                <label class="form-label" for="email-{{ $user->id }}">Login-E-Mail korrigieren</label>
                                <div class="input-group">
                                    <input class="form-control" id="email-{{ $user->id }}" type="email" name="email" value="{{ $user->email }}" required autocomplete="email">
                                    <button class="btn btn-outline-primary" type="submit">Ändern</button>
                                </div>
                            </form>
                        </div>
                        @can('delete', $user)
                            <div class="col-lg-5">
                                <form method="POST" action="{{ route('user-accounts.destroy', $user) }}" onsubmit="return confirm('Dieses nicht zugeordnete Benutzerkonto wirklich entfernen?')">
                                    @csrf
                                    @method('DELETE')
                                    <label class="form-label" for="password-{{ $user->id }}">Administratorpasswort</label>
                                    <div class="input-group">
                                        <input class="form-control" id="password-{{ $user->id }}" type="password" name="current_password" required autocomplete="current-password">
                                        <input type="hidden" name="confirmation" value="KONTO LÖSCHEN">
                                        <button class="btn btn-outline-danger" type="submit">Konto entfernen</button>
                                    </div>
                                </form>
                            </div>
                        @endcan
                    </div>
                </article>
            @empty
                <p class="mb-0 text-secondary">Es gibt keine nicht zugeordneten Benutzerkonten.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
