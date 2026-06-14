@extends('layouts.app')
@section('content')
<div class="container">
    <h1 class="h2 mb-4">Parzelle bearbeiten</h1>
    <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('parcels.update', $parcel) }}">
        @csrf
        @method('PUT')
        @include('parcels._form')
    </form>
</div>
@endsection
