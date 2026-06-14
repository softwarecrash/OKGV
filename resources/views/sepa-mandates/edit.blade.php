@extends('layouts.app')
@section('content')
<div class="container"><h1 class="h2 mb-4">SEPA-Mandat bearbeiten</h1><form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('sepa-mandates.update', $mandate) }}">@include('sepa-mandates._form')</form></div>
@endsection
