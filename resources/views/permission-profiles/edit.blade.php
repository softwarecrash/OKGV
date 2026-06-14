@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-1">Rechtevorlage bearbeiten</h1>
    <p class="text-secondary mb-4">Bestehende Konten werden durch diese Änderung nicht automatisch verändert.</p>
    <form method="POST" action="{{ route('permission-profiles.update', $profile) }}" class="card border-0 shadow-sm">
        <div class="card-body">@include('permission-profiles._form')</div>
    </form>
</div>
@endsection
