@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">Dashboard</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <h1 class="h4">Willkommen bei OKGV</h1>
                    <p>Die Stammdatenverwaltung für Mitglieder und Parzellen ist verfügbar.</p>
                    <div class="d-flex gap-2">
                        @can('viewAny', App\Models\Member::class)
                            <a class="btn btn-primary" href="{{ route('members.index') }}">Mitglieder</a>
                        @endcan
                        @can('viewAny', App\Models\Parcel::class)
                            <a class="btn btn-outline-primary" href="{{ route('parcels.index') }}">Parzellen</a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
