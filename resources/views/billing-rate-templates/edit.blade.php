@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-4">Preisvorlage bearbeiten</h1>
    <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('billing-rate-templates.update', $template) }}">
        @include('billing-rate-templates._form')
    </form>
</div>
@endsection
