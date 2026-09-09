@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><h1 class="h2">Vorstandsarbeit</h1><div class="d-flex gap-2">@can('create', App\Models\BoardMeeting::class)<a class="btn btn-primary" href="{{ route('board-meetings.create') }}">Sitzung anlegen</a>@endcan<a class="btn btn-outline-secondary" href="{{ route('board-meetings.book') }}">Beschlussbuch</a></div></div>
    <p class="text-secondary">Vertrauliche Sitzungen und Beschlüsse. Abgeschlossene Protokolle bleiben unveränderbar; offene Aufgaben bleiben auch bei archivierten Sitzungen sichtbar.</p>
    <x-validation-errors />
    <section id="tasks" class="card card-body mb-4">
        <h2 class="h4">{{ $due ? 'Fällige Aufgaben mit Handlungsbedarf für dich' : 'Offene Beschlussaufgaben' }}</h2>
        <div class="mb-2"><a href="{{ route('board-meetings.index', ['due' => $due ? 0 : 1, 'archived' => $archived ? 1 : 0]) }}#tasks">{{ $due ? 'Alle offenen Beschlussaufgaben anzeigen' : 'Nur fällige Aufgaben für mich anzeigen' }}</a></div>
        @include('board-meetings.tasks', ['showMeeting' => true])
        {{ $tasks->links() }}
    </section>
    <section class="card card-body">
        <div class="d-flex justify-content-between flex-wrap gap-2"><h2 class="h4">Sitzungen</h2><a href="{{ route('board-meetings.index', ['archived' => $archived ? 0 : 1, 'due' => $due ? 1 : 0]) }}">{{ $archived ? 'Archiv ausblenden' : 'Archiv einbeziehen' }}</a></div>
        <div class="table-responsive"><table class="table"><thead><tr><th>Termin</th><th>Sitzung</th><th>Ort</th><th>Status</th></tr></thead><tbody>
            @forelse ($meetings as $meeting)
                <tr><td>{{ $meeting->scheduled_at->format('d.m.Y H:i') }}</td><td><a href="{{ route('board-meetings.show', $meeting) }}">{{ $meeting->title }}</a></td><td>{{ $meeting->location ?: 'Noch offen' }}</td><td>{{ $meeting->statusLabel() }}</td></tr>
            @empty
                <tr><td colspan="4">Noch keine Sitzungen in dieser Auswahl. @can('create', App\Models\BoardMeeting::class)Über „Sitzung anlegen“ kannst du beginnen.@endcan</td></tr>
            @endforelse
        </tbody></table></div>
        {{ $meetings->links() }}
    </section>
</div>
@endsection
