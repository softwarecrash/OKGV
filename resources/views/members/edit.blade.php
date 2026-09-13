@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-4">Mitglied bearbeiten</h1>
    @if ($canViewUserAccess)
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item"><a class="nav-link @if (request('tab') !== 'access') active @endif" href="{{ route('members.edit', $member) }}">Stammdaten</a></li>
            <li class="nav-item"><a class="nav-link @if (request('tab') === 'access') active @endif" href="{{ route('members.edit', ['member' => $member, 'tab' => 'access']) }}">Zugang und Rechte</a></li>
        </ul>
    @endif

    @if (request('tab') === 'access' && $canViewUserAccess)
        @include('members._access-form')
    @else
        <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('members.update', $member) }}">
            @csrf
            @method('PUT')
            @include('members._form')
        </form>
    @endif
</div>
@endsection
