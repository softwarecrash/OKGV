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
                    @if (App\Models\ApplicationSetting::current()->logo_path)
                        <img src="{{ route('association-logo.show') }}" alt="" height="30" class="me-2">
                    @endif
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
                                $tenantPortalEnabled = App\Enums\FeatureModule::TenantPortal->enabled();
                                $metersEnabled = App\Enums\FeatureModule::Meters->enabled();
                                $billingEnabled = App\Enums\FeatureModule::Billing->enabled();
                                $workHoursEnabled = App\Enums\FeatureModule::WorkHours->enabled();
                                $workEventsEnabled = App\Enums\FeatureModule::WorkEvents->enabled();
                                $sepaEnabled = App\Enums\FeatureModule::Sepa->enabled();
                                $communicationEnabled = App\Enums\FeatureModule::Communication->enabled();
                                $documentsEnabled = App\Enums\FeatureModule::Documents->enabled();
                                $waitingListEnabled = App\Enums\FeatureModule::WaitingList->enabled();
                                $inventoryEnabled = App\Enums\FeatureModule::Inventory->enabled();
                                $canViewMembers = auth()->user()->can('viewAny', App\Models\Member::class);
                                $canViewRegistrations = $tenantPortalEnabled && auth()->user()->can('viewAny', App\Models\RegistrationRequest::class);
                                $canViewWaitingList = $waitingListEnabled && auth()->user()->can('viewAny', App\Models\WaitingListEntry::class);
                                $canViewTenantTransitions = auth()->user()->can('viewAny', App\Models\TenantTransition::class);
                                $canViewMeters = $metersEnabled && auth()->user()->can('viewAny', App\Models\Meter::class);
                                $canViewMeterSubmissions = $metersEnabled && auth()->user()->can('viewAny', App\Models\MeterReadingSubmission::class);
                                $canViewBilling = $billingEnabled && auth()->user()->can('viewAny', App\Models\BillingPeriod::class);
                                $canViewTemplates = $billingEnabled && auth()->user()->can('viewAny', App\Models\BillingRateTemplate::class);
                                $canViewInvoices = $billingEnabled && auth()->user()->can('viewAny', App\Models\Invoice::class);
                                $canViewWorkHours = $workHoursEnabled && auth()->user()->can('viewAny', App\Models\WorkHour::class);
                                $canViewWorkEvents = $workEventsEnabled && auth()->user()->can('viewAny', App\Models\WorkEvent::class);
                                $canViewWorkHourSubmissions = $workHoursEnabled && auth()->user()->can('viewAny', App\Models\WorkHourSubmission::class);
                                $canViewSepa = $sepaEnabled && auth()->user()->can('viewAny', App\Models\SepaMandate::class);
                                $canViewCommunication = $communicationEnabled && auth()->user()->can('viewAny', App\Models\MailCampaign::class);
                                $canViewDocuments = $documentsEnabled && auth()->user()->can('viewAny', App\Models\Document::class);
                                $canViewInventory = $inventoryEnabled && auth()->user()->can('viewAny', App\Models\InventoryItem::class);
                            @endphp
                            @if ($tenantPortalEnabled && auth()->user()->role === App\Enums\UserRole::Tenant)
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('tenant-portal.index') }}">
                                        Mein Portal
                                        <x-action-indicator
                                            :count="$actionIndicators['total']"
                                            :label="$actionIndicators['total'] === 1 ? 'offene Aufgabe' : 'offene Aufgaben'" />
                                    </a>
                                </li>
                            @endif
                            @if ($canViewMembers || $canViewRegistrations || $canViewWaitingList || $canViewTenantTransitions)
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                        Mitglieder
                                        <x-action-indicator :count="$actionIndicators['members_group']" label="offene Vorgänge im Mitgliederbereich" />
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
                                        @if ($canViewWaitingList)
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center justify-content-between gap-3" href="{{ route('waiting-list-entries.index') }}">
                                                    Warteliste
                                                    <x-action-indicator :count="$actionIndicators['waiting_list']" label="offene Wartelisteneinträge" />
                                                </a>
                                            </li>
                                        @endif
                                        @if ($canViewTenantTransitions)
                                            <li><a class="dropdown-item" href="{{ route('tenant-transitions.index') }}">Pächterwechsel</a></li>
                                        @endif
                                    </ul>
                                </li>
                            @endif
                            @can('viewAny', App\Models\Parcel::class)
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('parcels.index') }}">Parzellen</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('parcel-map.index') }}">Lageplan</a>
                                </li>
                            @endcan
                            @if ($canViewMeters || $canViewMeterSubmissions)
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                        Zähler
                                        <x-action-indicator
                                            :count="$actionIndicators['meters_group']"
                                            :label="$actionIndicators['meters_group'] === 1 ? 'offene Zählerstandsmeldung' : 'offene Zählerstandsmeldungen'" />
                                    </a>
                                    <ul class="dropdown-menu">
                                        @if ($canViewMeters)
                                            <li><a class="dropdown-item" href="{{ route('meters.index') }}">Zählerübersicht</a></li>
                                        @endif
                                        @if ($canViewMeterSubmissions)
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center justify-content-between gap-3" href="{{ route('meter-reading-submissions.index') }}">
                                                    Zählerstandsmeldungen
                                                    <x-action-indicator
                                                        :count="$actionIndicators['meter_readings']"
                                                        :label="$actionIndicators['meter_readings'] === 1 ? 'offene Zählerstandsmeldung' : 'offene Zählerstandsmeldungen'" />
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </li>
                            @endif
                            @if ($canViewBilling || $canViewTemplates || $canViewInvoices || $canViewWorkHours || $canViewWorkEvents || $canViewWorkHourSubmissions || $canViewSepa)
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                        Finanzen
                                        <x-action-indicator :count="$actionIndicators['finance_group']" label="offene Aufgaben im Finanzbereich" />
                                    </a>
                                    <ul class="dropdown-menu">
                                        @if ($canViewBilling)
                                            <li><a class="dropdown-item" href="{{ route('billing-periods.index') }}">Abrechnungsperioden</a></li>
                                        @endif
                                        @if ($canViewTemplates)
                                            <li><a class="dropdown-item" href="{{ route('billing-rate-templates.index') }}">Preisvorlagen</a></li>
                                        @endif
                                        @if ($canViewWorkHours)
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center justify-content-between gap-3" href="{{ route('work-hours.index') }}">
                                                    Arbeitsstunden
                                                    <x-action-indicator :count="$actionIndicators['work_hours']" label="offene Fehlstunden" />
                                                </a>
                                            </li>
                                        @endif
                                        @if ($canViewWorkEvents)
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center justify-content-between gap-3" href="{{ route('work-events.index') }}">
                                                    Arbeitseinsätze
                                                    <x-action-indicator :count="$actionIndicators['work_events']" label="überfällige Arbeitseinsätze" />
                                                </a>
                                            </li>
                                        @endif
                                        @if ($canViewWorkHourSubmissions)
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center justify-content-between gap-3" href="{{ route('work-hour-submissions.index') }}">
                                                    Arbeitsstundenmeldungen
                                                    <x-action-indicator :count="$actionIndicators['work_hour_submissions']" label="offene Arbeitsstundenmeldungen" />
                                                </a>
                                            </li>
                                        @endif
                                        @if ($canViewInvoices)
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center justify-content-between gap-3" href="{{ route('invoices.index') }}">
                                                    Rechnungen
                                                    <x-action-indicator :count="$actionIndicators['invoices']" label="offene Rechnungen" />
                                                </a>
                                            </li>
                                            @if (App\Enums\FeatureModule::Dunning->enabled() && auth()->user()->canManageBilling())
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center justify-content-between gap-3" href="{{ route('dunning-notices.index') }}">
                                                        Mahnwesen
                                                        <x-action-indicator :count="$actionIndicators['dunning_notices']" label="mahnfähige Rechnungen" />
                                                    </a>
                                                </li>
                                            @endif
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
                            @if ($canViewInventory)
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('inventory-items.index') }}">
                                        Inventar
                                        <x-action-indicator :count="$actionIndicators['inventory']" label="überfällige Inventarausgaben" />
                                    </a>
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
                                        <a class="dropdown-item" href="{{ route('number-sequences.edit') }}">
                                            Nummernkreise
                                        </a>
                                        <div class="dropdown-divider"></div>
                                    @endcan
                                    @if (App\Enums\FeatureModule::DataTransfer->enabled() && auth()->user()->canManageDataTransfer())
                                        <a class="dropdown-item" href="{{ route('data-transfer.index') }}">
                                            Datenübertragung
                                        </a>
                                        <div class="dropdown-divider"></div>
                                    @endif
                                    <a class="dropdown-item" href="{{ route('privacy.index') }}">
                                        Datenschutz
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <form action="{{ route('logout') }}" method="POST" data-logout-form>
                                        @csrf
                                        <button class="dropdown-item" type="submit">
                                            Abmelden
                                        </button>
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

        <footer class="container pb-4 text-center text-secondary small">
            <span>Version {{ config('app.version') }}</span>
            <span aria-hidden="true"> · </span>
            <span>GNU AGPLv3</span>
            <span aria-hidden="true"> · </span>
            <a href="{{ route('privacy.information') }}">Datenschutzinformationen</a>
            <span aria-hidden="true"> · </span>
            <a class="d-inline-flex align-items-center gap-1" href="{{ config('app.source_url') }}" target="_blank" rel="noopener noreferrer" aria-label="OKGV auf GitHub öffnen">
                <svg aria-hidden="true" width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M8 0C3.58 0 0 3.67 0 8.2c0 3.63 2.29 6.7 5.47 7.79.4.08.55-.18.55-.4 0-.2-.01-.86-.01-1.56-2.01.38-2.53-.5-2.69-.96-.09-.24-.48-.96-.82-1.15-.28-.16-.68-.55-.01-.56.63-.01 1.08.59 1.23.84.72 1.24 1.87.89 2.33.68.07-.53.28-.89.51-1.1-1.78-.21-3.64-.91-3.64-4.04 0-.89.31-1.62.82-2.19-.08-.21-.36-1.04.08-2.16 0 0 .67-.22 2.2.84A7.43 7.43 0 0 1 8 3.96c.68 0 1.36.09 2 .27 1.53-1.06 2.2-.84 2.2-.84.44 1.12.16 1.95.08 2.16.51.57.82 1.3.82 2.19 0 3.14-1.87 3.83-3.65 4.04.29.26.54.75.54 1.52 0 1.1-.01 1.98-.01 2.25 0 .22.15.48.55.4A8.08 8.08 0 0 0 16 8.2C16 3.67 12.42 0 8 0Z"/>
                </svg>
                <span>GitHub</span>
            </a>
            @if (config('app.donation_url'))
                <span aria-hidden="true"> · </span>
                <a href="{{ config('app.donation_url') }}" target="_blank" rel="noopener noreferrer">Spenden</a>
            @endif
        </footer>
    </div>
</body>
</html>
