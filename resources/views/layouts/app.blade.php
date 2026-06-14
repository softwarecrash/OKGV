<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'OKGV') }}</title>

    <script src="{{ asset('js/theme-init.js') }}"></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-lg bg-body shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    {{ config('app.name', 'OKGV') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Navigation umschalten">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">
                        @auth
                            @php
                                $canViewMembers = auth()->user()->can('viewAny', App\Models\Member::class);
                                $canViewRegistrations = auth()->user()->can('viewAny', App\Models\RegistrationRequest::class);
                                $canViewMeters = auth()->user()->can('viewAny', App\Models\Meter::class);
                                $canViewMeterSubmissions = auth()->user()->can('viewAny', App\Models\MeterReadingSubmission::class);
                                $canViewBilling = auth()->user()->can('viewAny', App\Models\BillingPeriod::class);
                                $canViewTemplates = auth()->user()->can('viewAny', App\Models\BillingRateTemplate::class);
                                $canViewInvoices = auth()->user()->can('viewAny', App\Models\Invoice::class);
                                $canViewSepa = auth()->user()->can('viewAny', App\Models\SepaMandate::class);
                                $canViewCommunication = auth()->user()->can('viewAny', App\Models\MailCampaign::class);
                                $canViewDocuments = auth()->user()->can('viewAny', App\Models\Document::class);
                            @endphp
                            @if (auth()->user()->role === App\Enums\UserRole::Tenant)
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('tenant-portal.index') }}">
                                        Mein Portal
                                        <x-action-indicator :count="$actionIndicators['total']" label="offene Aufgaben" />
                                    </a>
                                </li>
                            @endif
                            @if ($canViewMembers || $canViewRegistrations)
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                        Mitglieder
                                        <x-action-indicator :count="$actionIndicators['members_group']" label="wartende Registrierungen" />
                                    </a>
                                    <ul class="dropdown-menu">
                                        @if ($canViewMembers)
                                            <li><a class="dropdown-item" href="{{ route('members.index') }}">Mitgliederübersicht</a></li>
                                        @endif
                                        @if ($canViewRegistrations)
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center justify-content-between gap-3" href="{{ route('registration-requests.index') }}">
                                                    Registrierungsanfragen
                                                    <x-action-indicator :count="$actionIndicators['registrations']" label="wartende Registrierungen" />
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </li>
                            @endif
                            @can('viewAny', App\Models\Parcel::class)
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('parcels.index') }}">Parzellen</a>
                                </li>
                            @endcan
                            @if ($canViewMeters || $canViewMeterSubmissions)
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                        Zähler
                                        <x-action-indicator :count="$actionIndicators['meters_group']" label="offene Zählerstandsmeldungen" />
                                    </a>
                                    <ul class="dropdown-menu">
                                        @if ($canViewMeters)
                                            <li><a class="dropdown-item" href="{{ route('meters.index') }}">Zählerübersicht</a></li>
                                        @endif
                                        @if ($canViewMeterSubmissions)
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center justify-content-between gap-3" href="{{ route('meter-reading-submissions.index') }}">
                                                    Zählerstandsmeldungen
                                                    <x-action-indicator :count="$actionIndicators['meter_readings']" label="offene Zählerstandsmeldungen" />
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </li>
                            @endif
                            @if ($canViewBilling || $canViewTemplates || $canViewInvoices || $canViewSepa)
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                        Finanzen
                                        <x-action-indicator :count="$actionIndicators['finance_group']" label="offene Rechnungen" />
                                    </a>
                                    <ul class="dropdown-menu">
                                        @if ($canViewBilling)
                                            <li><a class="dropdown-item" href="{{ route('billing-periods.index') }}">Abrechnungsperioden</a></li>
                                        @endif
                                        @if ($canViewTemplates)
                                            <li><a class="dropdown-item" href="{{ route('billing-rate-templates.index') }}">Preisvorlagen</a></li>
                                        @endif
                                        @if ($canViewInvoices)
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center justify-content-between gap-3" href="{{ route('invoices.index') }}">
                                                    Rechnungen
                                                    <x-action-indicator :count="$actionIndicators['invoices']" label="offene Rechnungen" />
                                                </a>
                                            </li>
                                        @endif
                                        @if ($canViewSepa)
                                            <li><hr class="dropdown-divider"></li>
                                            <li><h6 class="dropdown-header">SEPA</h6></li>
                                            <li><a class="dropdown-item" href="{{ route('sepa-mandates.index') }}">Mandate</a></li>
                                            <li><a class="dropdown-item" href="{{ route('payment-batches.index') }}">Sammellastschriften</a></li>
                                            <li><a class="dropdown-item" href="{{ route('sepa-settings.edit') }}">SEPA-Einstellungen</a></li>
                                        @endif
                                    </ul>
                                </li>
                            @endif
                            @if ($canViewCommunication)
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                        Kommunikation
                                        <x-action-indicator :count="$actionIndicators['communication_group']" label="fehlgeschlagene Serienmails" />
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center justify-content-between gap-3" href="{{ route('mail-campaigns.index') }}">
                                                Serienmails
                                                <x-action-indicator :count="$actionIndicators['communication_group']" label="fehlgeschlagene Serienmails" />
                                            </a>
                                        </li>
                                        <li><a class="dropdown-item" href="{{ route('letters.index') }}">PDF-Briefe</a></li>
                                    </ul>
                                </li>
                            @endif
                            @if ($canViewDocuments)
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('documents.index') }}">Dokumente</a>
                                </li>
                            @endif
                        @endauth
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item d-flex align-items-center">
                            <button class="btn btn-sm btn-outline-secondary theme-toggle mx-2"
                                    type="button"
                                    data-theme-toggle
                                    aria-label="Darstellungsmodus wechseln"
                                    title="Darstellungsmodus wechseln">
                                <span data-theme-icon aria-hidden="true">◐</span>
                                <span class="d-lg-none ms-1" data-theme-label>Darstellung</span>
                            </button>
                        </li>
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">Anmelden</a>
                                </li>
                            @endif

                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    {{ Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    @can('viewAny', App\Models\User::class)
                                        <a class="dropdown-item" href="{{ route('user-permissions.index') }}">
                                            Rechteverwaltung
                                        </a>
                                        <a class="dropdown-item" href="{{ route('application-settings.edit') }}">
                                            Globale Konfiguration
                                        </a>
                                        <div class="dropdown-divider"></div>
                                    @endcan
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        Abmelden
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            @if (session('status'))
                <div class="container">
                    <div class="alert alert-success" role="alert">{{ session('status') }}</div>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</body>
</html>
