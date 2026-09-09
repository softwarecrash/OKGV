@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between gap-2 mb-3"><h1 class="h2">Beschlussbuch</h1><a class="btn btn-outline-secondary" href="{{ route('board-meetings.index') }}">Sitzungen</a></div>
    <p class="text-secondary">Beschlüsse abgeschlossener Sitzungen, einschließlich archivierter Protokolle. Die Referenz bleibt unverändert; Nummernlücken sind möglich.</p>
    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-6"><label for="q" class="form-label">Titel oder Beschlusstext suchen</label><input class="form-control" name="q" id="q" maxlength="180" value="{{ request('q') }}"></div>
        <div class="col-md-4"><label for="result" class="form-label">Ergebnis</label><select class="form-select" name="result" id="result"><option value="">Alle Ergebnisse</option>@foreach (App\Enums\BoardResolutionResult::cases() as $result)<option value="{{ $result->value }}" @selected(request('result') === $result->value)>{{ $result->label() }}</option>@endforeach</select></div>
        <div class="col-md-2 align-self-end"><button class="btn btn-primary">Suchen</button></div>
    </form>
    <x-validation-errors />
    @forelse ($resolutions as $resolution)
        <article class="card card-body mb-3"><h2 class="h5">Beschluss #{{ $resolution->id }}: {{ $resolution->title }}</h2><p>{{ $resolution->meeting->scheduled_at->format('d.m.Y') }} · {{ $resolution->result->label() }}</p><div>{!! nl2br(e($resolution->body)) !!}</div><a class="mt-2" href="{{ route('board-meetings.show', $resolution->meeting) }}#resolution-{{ $resolution->id }}">Sitzung, Protokoll und Aufgaben öffnen</a></article>
    @empty
        <p class="text-secondary">Keine abgeschlossenen Beschlüsse in dieser Auswahl.</p>
    @endforelse
    {{ $resolutions->links() }}
</div>
@endsection
