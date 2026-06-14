@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-1">Dokument hochladen</h1>
    <p class="text-secondary mb-4">Datei sicher ablegen, fachlich zuordnen und gezielt freigeben.</p>

    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="card border-0 shadow-sm">
        @csrf
        @include('documents._form')
    </form>
</div>
@endsection
