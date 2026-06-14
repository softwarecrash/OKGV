@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-1">Dokument bearbeiten</h1>
    <p class="text-secondary mb-4">Metadaten ändern oder eine neue unveränderliche Dateiversion ablegen.</p>

    <form method="POST" action="{{ route('documents.update', $document) }}" enctype="multipart/form-data" class="card border-0 shadow-sm">
        @csrf
        @method('PUT')
        @include('documents._form')
    </form>
</div>
@endsection
