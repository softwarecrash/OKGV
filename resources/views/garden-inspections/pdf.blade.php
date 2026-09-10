<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Gartenbegehung: {{ $inspection->title }}</title>
    <style>
        @page { margin: 20mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10pt; color: #263238; line-height: 1.5; }
        h1 { color: #2E7D32; font-size: 18pt; }
        h2 { font-size: 13pt; margin-top: 8mm; }
        .finding { border-top: 1px solid #cfd8dc; padding-top: 4mm; margin-top: 4mm; }
        .note { color: #455a64; font-size: 8pt; }
    </style>
</head>
<body>
    <h1>{{ $inspection->title }}</h1>
    <p>Gartenbegehung vom {{ $inspection->inspected_at->format('d.m.Y H:i') }}<br>Prüfer: {{ $inspection->inspectors }}</p>
    @if ($inspection->instructions)<p><strong>Interne Anleitung:</strong><br>{!! nl2br(e($inspection->instructions)) !!}</p>@endif
    <h2>Feststellungen</h2>
    @foreach ($inspection->findings as $finding)
        <div class="finding">
            <strong>Parzelle {{ $finding->parcel->parcel_number }} · {{ ucfirst($finding->category) }} · {{ $finding->status->label() }}</strong><br>
            {!! nl2br(e($finding->description)) !!}
            @if ($finding->due_at)<br><span class="note">Frist: {{ $finding->due_at->format('d.m.Y') }}</span>@endif
            @if ($finding->responsibleMember)<br><span class="note">Zuständig: {{ $finding->responsibleMember->full_name }}</span>@endif
            @if ($finding->task)<br><span class="note">Verknüpfte Aufgabe: {{ $finding->task->title }}</span>@endif
            @if ($finding->internal_note)<br><span class="note">Interne Notiz: {!! nl2br(e($finding->internal_note)) !!}</span>@endif
        </div>
    @endforeach
    <p class="note">Abgeschlossen am {{ $inspection->finalized_at?->format('d.m.Y H:i') }}. Dieses Protokoll ist intern und wird privat gespeichert.</p>
</body>
</html>
