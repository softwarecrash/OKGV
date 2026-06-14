@extends('layouts.app')

@section('content')
<div class="container">
    <div class="mb-4">
        <h1 class="h2 mb-1">Arbeitsstunden erfassen</h1>
        <p class="text-secondary mb-0">{{ $billingPeriod->name }}</p>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if ($members->isEmpty())
                <div class="alert alert-warning mb-0">
                    Für alle aktiven Mitglieder wurde in dieser Periode bereits ein Arbeitsstundenkonto angelegt.
                </div>
            @else
                <form method="POST" action="{{ route('billing-periods.work-hours.store', $billingPeriod) }}">
                    @include('work-hours._form')
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
