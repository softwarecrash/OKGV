@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-4">Mitglied bearbeiten</h1>
    <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('members.update', $member) }}">
        @csrf
        @method('PUT')
        @include('members._form')
    </form>
</div>
@endsection
