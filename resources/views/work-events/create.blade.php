@extends('layouts.app')

@section('content')
<div class="container">
    <div class="mb-4">
        <h1 class="h2 mb-1">Arbeitseinsatz anlegen</h1>
        <p class="text-secondary mb-0">{{ $billingPeriod->name }}</p>
    </div>
    <form class="card border-0 shadow-sm" method="POST" action="{{ route('billing-periods.work-events.store', $billingPeriod) }}">
        <div class="card-body">@include('work-events._form')</div>
    </form>
</div>
@endsection
