@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">Dashboard</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <h1 class="h4">Willkommen bei {{ config('app.name', 'OKGV') }}</h1>
                    <p class="text-secondary">Wähle den Bereich aus, in dem du arbeiten möchtest. Angezeigte Funktionen richten sich nach deinen Berechtigungen.</p>
                    <div class="row g-3">
                        @if (App\Enums\FeatureModule::TenantPortal->enabled() && auth()->user()->role === App\Enums\UserRole::Tenant)
                            <div class="col-12"><a class="btn btn-primary w-100" href="{{ route('tenant-portal.index') }}">Mein Pächterportal öffnen</a></div>
                        @endif
                        @can('viewAny', App\Models\Member::class)
                            <div class="col-md-6"><a class="btn btn-primary w-100" href="{{ route('members.index') }}">Mitglieder verwalten</a></div>
                        @endcan
                        @can('viewAny', App\Models\Parcel::class)
                            <div class="col-md-6"><a class="btn btn-outline-primary w-100" href="{{ route('parcels.index') }}">Parzellen und Pächter</a></div>
                        @endcan
                        @if (App\Enums\FeatureModule::Meters->enabled())
                        @can('viewAny', App\Models\Meter::class)
                            <div class="col-md-6"><a class="btn btn-outline-primary w-100" href="{{ route('meters.index') }}">Zähler und Ablesungen</a></div>
                        @endcan
                        @endif
                        @if (App\Enums\FeatureModule::Billing->enabled())
                        @can('viewAny', App\Models\BillingPeriod::class)
                            <div class="col-md-6"><a class="btn btn-outline-primary w-100" href="{{ route('billing-periods.index') }}">Abrechnung vorbereiten</a></div>
                        @endcan
                        @can('viewAny', App\Models\Invoice::class)
                            <div class="col-md-6"><a class="btn btn-outline-primary w-100" href="{{ route('invoices.index') }}">Rechnungen ansehen</a></div>
                        @endcan
                        @endif
                        @if (App\Enums\FeatureModule::Communication->enabled())
                        @can('viewAny', App\Models\MailCampaign::class)
                            <div class="col-md-6"><a class="btn btn-outline-primary w-100" href="{{ route('mail-campaigns.index') }}">Kommunikation verwalten</a></div>
                        @endcan
                        @endif
                        @if (App\Enums\FeatureModule::Documents->enabled())
                        @can('viewAny', App\Models\Document::class)
                            <div class="col-md-6"><a class="btn btn-outline-primary w-100" href="{{ route('documents.index') }}">Dokumente verwalten</a></div>
                        @endcan
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
