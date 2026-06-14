@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-4">Abrechnungsperiode anlegen</h1>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('billing-periods.store') }}">
                @include('billing-periods._form')
            </form>
        </div>
    </div>
</div>
@endsection
