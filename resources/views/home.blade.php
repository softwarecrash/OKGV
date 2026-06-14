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

                    <h1 class="h4">Willkommen bei OKGV</h1>
                    <p class="text-secondary">Wähle den Bereich aus, in dem du arbeiten möchtest. Angezeigte Funktionen richten sich nach deinen Berechtigungen.</p>
                    <div class="row g-3">
                        @can('viewAny', App\Models\Member::class)
                            <div class="col-md-6"><a class="btn btn-primary w-100" href="{{ route('members.index') }}">Mitglieder verwalten</a></div>
                        @endcan
                        @can('viewAny', App\Models\Parcel::class)
                            <div class="col-md-6"><a class="btn btn-outline-primary w-100" href="{{ route('parcels.index') }}">Parzellen und Pächter</a></div>
                        @endcan
                        @can('viewAny', App\Models\Meter::class)
                            <div class="col-md-6"><a class="btn btn-outline-primary w-100" href="{{ route('meters.index') }}">Zähler und Ablesungen</a></div>
                        @endcan
                        @can('viewAny', App\Models\BillingPeriod::class)
                            <div class="col-md-6"><a class="btn btn-outline-primary w-100" href="{{ route('billing-periods.index') }}">Abrechnung vorbereiten</a></div>
                        @endcan
                        @can('viewAny', App\Models\Invoice::class)
                            <div class="col-md-6"><a class="btn btn-outline-primary w-100" href="{{ route('invoices.index') }}">Rechnungen ansehen</a></div>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
