@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><h1 class="h2">{{ $meeting->title }}</h1><div class="d-flex gap-2">@can('update', $meeting)<a class="btn btn-primary" href="{{ route('board-meetings.edit', $meeting) }}">Sitzung / Protokoll bearbeiten</a>@endcan @if ($meeting->finalized_at)<a class="btn btn-outline-primary" href="{{ route('board-meetings.pdf', $meeting) }}">Protokoll als PDF</a>@endif<a class="btn btn-outline-secondary" href="{{ route('board-meetings.index') }}">Übersicht</a></div></div>
    <x-validation-errors />
    <section class="card card-body mb-4">
        <p>{{ $meeting->scheduled_at->format('d.m.Y H:i') }} ({{ config('app.timezone') }}) · {{ $meeting->location ?: 'Ort noch offen' }} · <strong>{{ $meeting->statusLabel() }}</strong></p>
        <h2 class="h5">Teilnehmer</h2><p>{!! nl2br(e($meeting->attendees ?: 'Noch nicht erfasst')) !!}</p>
        <h2 class="h5">Allgemeines Protokoll</h2><div>{!! nl2br(e($meeting->minutes ?: 'Noch nicht erfasst')) !!}</div>
        @if ($meeting->finalized_at)<p class="text-secondary mt-3 mb-0">Abgeschlossen am {{ $meeting->finalized_at->format('d.m.Y H:i') }}. Korrekturen bitte in einer neuen Sitzung mit Verweis auf den ursprünglichen Beschluss dokumentieren.</p>@endif
    </section>
    <section class="card card-body mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><h2 class="h4">Tagesordnung</h2>@can('update', $meeting)<a class="btn btn-sm btn-outline-primary" href="{{ route('board-agenda.create', $meeting) }}">Thema ergänzen</a>@endcan</div>
        @forelse ($meeting->agendaItems as $topic)
            <article class="border-top py-3"><h3 class="h5">{{ $topic->position }}. {{ $topic->title }}</h3><p>{!! nl2br(e($topic->description)) !!}</p><div>{!! nl2br(e($topic->minutes)) !!}</div>@can('update', $meeting)<a href="{{ route('board-agenda.edit', [$meeting, $topic]) }}">Thema / Protokoll bearbeiten</a>@endcan</article>
        @empty
            <p class="text-secondary">Noch keine Themen erfasst.</p>
        @endforelse
    </section>
    <section class="card card-body mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><h2 class="h4">Beschlüsse</h2>@can('update', $meeting)<a class="btn btn-sm btn-outline-primary" href="{{ route('board-resolutions.create', $meeting) }}">Beschluss ergänzen</a>@endcan</div>
        @forelse ($meeting->resolutions as $resolution)
            <article id="resolution-{{ $resolution->id }}" class="border-top py-3">
                <h3 class="h5">Beschluss #{{ $resolution->id }}: {{ $resolution->title }}</h3><p><strong>{{ $resolution->result->label() }}</strong>@if ($resolution->agendaItem) · Thema {{ $resolution->agendaItem->position }}: {{ $resolution->agendaItem->title }}@endif</p>
                <p>{!! nl2br(e($resolution->body)) !!}</p>
                @if ($resolution->votes_for !== null)<p>Ja: {{ $resolution->votes_for }} · Nein: {{ $resolution->votes_against }} · Enthaltungen: {{ $resolution->votes_abstained }}</p>@endif
                @can('update', $meeting)<a href="{{ route('board-resolutions.edit', [$meeting, $resolution]) }}">Beschluss bearbeiten</a>@endcan
                @if ($meeting->finalized_at && $resolution->result === App\Enums\BoardResolutionResult::Adopted)
                    <h4 class="h6 mt-3">Aufgaben / Wiedervorlagen</h4>
                    @include('board-meetings.tasks', ['tasks' => $resolution->followUps])
                    @can('create', App\Models\BoardMeeting::class)<a class="btn btn-sm btn-outline-primary" href="{{ route('board-follow-ups.create', $resolution) }}">Aufgabe aus Beschluss anlegen</a>@endcan
                @endif
            </article>
        @empty
            <p class="text-secondary">Noch keine Beschlüsse erfasst. Eine Sitzung kann auch ohne Beschluss abgeschlossen werden.</p>
        @endforelse
        @if (! $meeting->finalized_at)<p class="form-text">Beschlüsse erscheinen nach dem Protokollabschluss im Beschlussbuch. Anschließend können Aufgaben daraus angelegt werden.</p>@endif
    </section>
    @if (App\Enums\FeatureModule::Documents->enabled())
        <section class="card card-body mb-4"><h2 class="h4">Verknüpfte Dokumente</h2><p class="form-text">Es werden nur Dokumente angezeigt, für die du zusätzlich berechtigt bist. Die Links öffnen die aktuelle Dateiversion.</p>@forelse ($documents as $document)<p><a href="{{ route('board-meetings.document', [$meeting, $document]) }}">{{ $document->title }}</a></p>@empty<p class="text-secondary">Keine für dich zugreifbaren Dokumente verknüpft.</p>@endforelse</section>
    @endif
    @can('finalize', $meeting)
        <form class="card card-body mb-4" action="{{ route('board-meetings.finalize', $meeting) }}" method="POST">@csrf<h2 class="h4">Protokoll abschließen</h2><p>Teilnehmer, Protokoll und mindestens ein Tagesordnungspunkt müssen erfasst sein. Nach dem Abschluss sind Sitzungsdaten und Beschlüsse unveränderbar. Die PDF-Datei wird privat gesichert.</p><div class="form-check mb-3"><input class="form-check-input" id="confirmed" type="checkbox" name="confirmed" value="1" required><label class="form-check-label" for="confirmed">Ich habe das Protokoll geprüft und möchte es endgültig abschließen.</label></div><button class="btn btn-primary align-self-start">Verbindlich abschließen</button></form>
    @endcan
    @can('archive', $meeting)
        <form action="{{ route('board-meetings.archive', $meeting) }}" method="POST" class="mb-4">@csrf<p class="form-text">Archivieren blendet die Sitzung aus der Standardliste aus und beendet die Bearbeitung. Historie und offene Aufgaben bleiben erhalten.</p><button class="btn btn-outline-secondary">Sitzung archivieren</button></form>
    @endcan
    @can('unarchive', $meeting)
        <form action="{{ route('board-meetings.unarchive', $meeting) }}" method="POST" class="mb-4">@csrf<button class="btn btn-outline-secondary">Aus Archiv zurückholen</button><p class="form-text">Abgeschlossene Protokolle bleiben unveränderbar; archivierte Entwürfe können danach wieder bearbeitet werden.</p></form>
    @endcan
</div>
@endsection
