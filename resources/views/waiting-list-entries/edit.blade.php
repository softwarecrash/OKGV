@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-4">Wartelisteneintrag bearbeiten</h1>
    <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('waiting-list-entries.update', $entry) }}">
        @csrf
        @method('PUT')
        @include('waiting-list-entries._form')
    </form>
</div>
@endsection
