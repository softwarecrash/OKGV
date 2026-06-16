@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            @php
                $demoAccounts = collect(config('demo.accounts', []))
                    ->filter(fn (array $account): bool => filled($account['email'] ?? null) && filled($account['password'] ?? null))
                    ->values();
            @endphp

            @if (config('demo.enabled') && $demoAccounts->isNotEmpty())
                <div class="alert alert-info shadow-sm">
                    <h2 class="h5">Demo-Zugangsdaten</h2>
                    <p class="mb-3">
                        Diese Installation läuft im Demo-Modus. Wähle einen Zugang aus,
                        um E-Mail-Adresse und Passwort automatisch einzutragen.
                    </p>
                    <div class="row g-2">
                        @foreach ($demoAccounts as $account)
                            <div class="col-md-4">
                                <button class="btn btn-outline-primary w-100 h-100 text-start"
                                        type="button"
                                        data-demo-login
                                        data-demo-email="{{ $account['email'] }}"
                                        data-demo-password="{{ $account['password'] }}">
                                    <strong>{{ $account['label'] }}</strong><br>
                                    <span class="small">{{ $account['email'] }}</span><br>
                                    <span class="small text-secondary">{{ $account['description'] }}</span>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <div class="small mt-3">
                        Der Demo-Modus blockiert externen Mailversand und schützt die SMTP-Konfiguration.
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header">Bei {{ config('app.name', 'OKGV') }} anmelden</div>

                <div class="card-body">
                    <p class="text-secondary">Melde dich mit der E-Mail-Adresse und dem Passwort deines Vereinskonto an.</p>
                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label>

                            <div class="col-md-6">
                                <div class="input-group">
                                    <input id="password" type="password"
                                           class="form-control @error('password') is-invalid @enderror"
                                           name="password" required autocomplete="current-password">
                                    <button class="btn btn-outline-secondary" type="button"
                                            data-password-toggle
                                            aria-controls="password"
                                            aria-pressed="false"
                                            aria-label="Passwort anzeigen"
                                            title="Passwort anzeigen">
                                        <svg data-password-show-icon xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                            <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                                            <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M6.5 8a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0"/>
                                        </svg>
                                        <svg class="d-none" data-password-hide-icon xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                            <path d="m10.79 12.912-1.614-1.615a4 4 0 0 1-4.474-4.474L2.77 4.89C1.445 6.015.564 7.322 0 8c0 0 3 5.5 8 5.5a7 7 0 0 0 2.79-.588M5.21 3.088A7 7 0 0 1 8 2.5c5 0 8 5.5 8 5.5a13 13 0 0 1-2.166 2.697l-1.516-1.516A3.99 3.99 0 0 0 6.82 3.682z"/>
                                            <path d="M11.854 8.717 8.288 5.152A2.5 2.5 0 0 1 11.854 8.718zM6.5 7.207l2.293 2.293A1.5 1.5 0 0 1 6.5 7.207M13.646 14.354l-12-12 .708-.708 12 12z"/>
                                        </svg>
                                    </button>

                                    @error('password')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 offset-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>

                                    <label class="form-check-label" for="remember">
                                        {{ __('Remember Me') }}
                                    </label>
                                    <div class="form-text">Nur auf einem persönlichen Gerät verwenden.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Login') }}
                                </button>

                                @if (Route::has('password.request'))
                                    <a class="btn btn-link" href="{{ route('password.request') }}">
                                        {{ __('Forgot Your Password?') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                    <hr>
                    <p class="mb-0 text-center">
                        Noch kein Pächterkonto?
                        @if (App\Enums\FeatureModule::TenantPortal->enabled())
                            <a href="{{ route('tenant-registration.create') }}">Zugang mit Parzellennummer beantragen</a>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
