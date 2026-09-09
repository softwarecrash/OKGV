<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Vorstandsprotokoll: {{ $meeting->title }}</title>
    <style>
        @page { margin: 20mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10pt; color: #263238; line-height: 1.5; overflow-wrap: anywhere; }
        h1 { font-size: 18pt; color: #2E7D32; }
        h2 { font-size: 14pt; margin-top: 8mm; }
        h3 { font-size: 11pt; }
        .logo { max-height: 18mm; max-width: 55mm; }
        .notice { color: #555; font-size: 8pt; }
    </style>
</head>
<body>
    @if ($association['logo_data_uri'])<img class="logo" src="{{ $association['logo_data_uri'] }}" alt="">@endif
    <p>{{ $association['name'] }}</p>
    <h1>{{ $meeting->title }}</h1>
    <p>Vorstandsprotokoll · {{ $meeting->scheduled_at->format('d.m.Y H:i') }} ({{ config('app.timezone') }})<br>Ort: {{ $meeting->location ?: 'Nicht angegeben' }}</p>
    <p class="notice">Vertraulich. Abgeschlossen am {{ $meeting->finalized_at->format('d.m.Y H:i') }}. Sitzung #{{ $meeting->id }}.</p>
    <h2>Teilnehmer</h2><div>{!! nl2br(e($meeting->attendees)) !!}</div>
    <h2>Allgemeines Protokoll</h2><div>{!! nl2br(e($meeting->minutes)) !!}</div>
    <h2>Tagesordnung und Verlauf</h2>
    @foreach ($meeting->agendaItems as $topic)
        <h3>{{ $topic->position }}. {{ $topic->title }}</h3><p>{!! nl2br(e($topic->description)) !!}</p><div>{!! nl2br(e($topic->minutes)) !!}</div>
    @endforeach
    <h2>Beschlüsse</h2>
    @forelse ($meeting->resolutions as $resolution)
        <h3>Beschluss #{{ $resolution->id }}: {{ $resolution->title }}</h3><p>{{ $resolution->result->label() }}@if ($resolution->agendaItem) · Tagesordnungspunkt {{ $resolution->agendaItem->position }}@endif</p><div>{!! nl2br(e($resolution->body)) !!}</div>
        @if ($resolution->votes_for !== null)<p>Ja: {{ $resolution->votes_for }} · Nein: {{ $resolution->votes_against }} · Enthaltungen: {{ $resolution->votes_abstained }}</p>@endif
    @empty
        <p>Keine Beschlüsse erfasst.</p>
    @endforelse
</body>
</html>
