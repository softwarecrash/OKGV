@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2">Umfragen und Terminabfragen</h1>
    <p class="text-secondary">{{ $manage ? 'Verwalte Entwürfe, veröffentlichte Abfragen und abgeschlossene Ergebnisse.' : 'Hier findest du die Abfragen, zu denen dein Konto eingeladen wurde und deren Zielgruppe du weiterhin angehörst.' }}</p>
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a class="btn btn-outline-primary" href="{{ route('polls.index') }}">Meine Umfragen</a>
        <a class="btn btn-outline-primary" href="{{ route('polls.index', ['unanswered' => 1]) }}">Teilnahme offen</a>
        @can('create', App\Models\Poll::class)
            <a class="btn btn-primary" href="{{ route('polls.create') }}">Umfrage anlegen</a>
            <a class="btn btn-outline-secondary" href="{{ route('polls.index', ['manage' => 1]) }}">Alle verwalten</a>
        @endcan
        <a class="btn btn-outline-secondary" href="{{ route('polls.index', ['manage' => (int) $manage, 'archived' => 1]) }}">Archiv</a>
    </div>
    @forelse ($polls as $poll)
        <article class="card card-body mb-3">
            <h2 class="h5"><a href="{{ route('polls.show', $poll) }}">{{ $poll->title }}</a></h2>
            <p class="mb-1">{{ $poll->type->label() }} · {{ $poll->statusLabel() }} · {{ $poll->starts_at->format('d.m.Y H:i') }} bis {{ $poll->ends_at->format('d.m.Y H:i') }}</p>
            @if ($poll->has_answered)<span class="text-success">Deine Antwort ist gespeichert.</span>
            @else
                @can('answer', $poll)<span><x-action-indicator :count="1" /> Teilnahme offen</span>@endcan
            @endif
        </article>
    @empty
        <div class="alert alert-info">Hier gibt es derzeit keine passenden Umfragen. Bereits beantwortete Abfragen findest du unter „Meine Umfragen“.</div>
    @endforelse
    {{ $polls->links() }}
</div>
@endsection
