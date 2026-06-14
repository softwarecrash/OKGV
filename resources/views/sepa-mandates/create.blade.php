@extends('layouts.app')
@section('content')
<div class="container"><h1 class="h2 mb-4">SEPA-Mandat anlegen</h1><form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('sepa-mandates.store') }}">@include('sepa-mandates._form')</form></div>
@endsection
