@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
        <div><h1 class="h2 mb-1">{{ $letter->subject }}</h1><p class="text-secondary mb-0">Gespeichert am {{ $letter->created_at->format('d.m.Y H:i') }}</p></div>
        <a class="btn btn-primary" href="{{ route('letters.pdf', $letter) }}">PDF herunterladen</a>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <address><strong>{{ $letter->recipient_name }}</strong><br>{{ $letter->street }}<br>{{ $letter->zip }} {{ $letter->city }}</address>
            <hr>
            <div>{!! nl2br(e($letter->body)) !!}</div>
        </div>
    </div>
</div>
@endsection
