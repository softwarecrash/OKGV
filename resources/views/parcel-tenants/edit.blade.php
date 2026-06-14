@extends('layouts.app')
@section('content')
<div class="container">
    <h1 class="h2 mb-4">Pächterzuordnung bearbeiten</h1>
    <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('parcel-tenants.update', $parcelTenant) }}">
        @csrf
        @method('PUT')
        @include('parcel-tenants._form')
    </form>
</div>
@endsection
