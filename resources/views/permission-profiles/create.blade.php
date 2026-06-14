@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-1">Rechtevorlage anlegen</h1>
    <p class="text-secondary mb-4">Bündele nur die Rechte, die für eine konkrete Vorstandsaufgabe benötigt werden.</p>
    <form method="POST" action="{{ route('permission-profiles.store') }}" class="card border-0 shadow-sm">
        <div class="card-body">@include('permission-profiles._form')</div>
    </form>
</div>
@endsection
