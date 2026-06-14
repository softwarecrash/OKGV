@extends('layouts.app')

@section('content')
<div class="container">
    <div class="mb-4">
        <h1 class="h2 mb-1">Arbeitsstunden bearbeiten</h1>
        <p class="text-secondary mb-0">{{ $workHour->member->full_name }} · {{ $billingPeriod->name }}</p>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('work-hours.update', $workHour) }}">
                @include('work-hours._form')
            </form>
        </div>
    </div>
</div>
@endsection
