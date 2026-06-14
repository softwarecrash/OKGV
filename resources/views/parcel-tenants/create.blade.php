@extends('layouts.app')
@section('content')
<div class="container">
    <h1 class="h2 mb-4">Pächter zuordnen</h1>
    <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('parcel-tenants.store') }}">
        @csrf
        @include('parcel-tenants._form')
    </form>
</div>
@endsection
