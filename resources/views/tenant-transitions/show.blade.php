@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h2 mb-1">Pächterwechsel · Parzelle {{ $transition->parcel->parcel_number }}</h1>
            <p class="text-secondary mb-0">Übergabe am {{ $transition->transfer_date->format('d.m.Y') }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('tenant-transitions.index') }}">Zur Übersicht</a>
    </div>

    <div class="alert alert-success">
        Der Übergabevorgang wurde am {{ $transition->completed_at->format('d.m.Y H:i') }} durch {{ $transition->completer->name }} vollständig abgeschlossen und ist unveränderlich.
    </div>

    <div class="row g-4">
        @foreach ([
            'Bisherige Vertragsparteien' => $transition->outgoing_members_snapshot,
            'Neue Vertragsparteien' => $transition->incoming_members_snapshot,
        ] as $heading => $members)
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header">{{ $heading }}</div>
                    <div class="list-group list-group-flush">
                        @foreach ($members as $member)
                            <div class="list-group-item">
                                <strong>{{ $member['first_name'] }} {{ $member['last_name'] }}</strong>
                                @if ($member['is_primary'])
                                    <span class="badge text-bg-primary ms-1">Hauptpächter</span>
                                @endif
                                <div class="small text-secondary">
                                    {{ $member['member_number'] }} · {{ $member['street'] }}, {{ $member['zip'] }} {{ $member['city'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header">Übergabezählerstände</div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Typ</th><th>Zählernummer</th><th>Stand</th><th>Datum</th></tr></thead>
                <tbody>
                    @forelse ($transition->meterReadings as $reading)
                        <tr>
                            <td>{{ $reading->meter->type->label() }}</td>
                            <td><a href="{{ route('meters.show', $reading->meter) }}">{{ $reading->meter->meter_number }}</a></td>
                            <td>{{ number_format((float) $reading->reading_value, 4, ',', '.') }} {{ $reading->meter->type->unit() }}</td>
                            <td>{{ $transition->transfer_date->format('d.m.Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-4 text-secondary">Keine Zähler vorhanden.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header">Offene Forderungen zum Übergabezeitpunkt</div>
        <div class="card-body">
            <p class="text-secondary">Diese Forderungen verbleiben bei den bisherigen Vertragsparteien und wurden nicht übertragen.</p>
            @forelse ($transition->open_claims_snapshot ?? [] as $claim)
                <div class="d-flex flex-wrap justify-content-between gap-2 border-top py-2">
                    <span>{{ $claim['invoice_number'] }} · {{ $claim['member_name'] }} · fällig {{ \Carbon\CarbonImmutable::parse($claim['due_at'])->format('d.m.Y') }}</span>
                    <strong>{{ number_format((float) $claim['total_amount'], 2, ',', '.') }} € · {{ $claim['payment_status_label'] }}</strong>
                </div>
            @empty
                <div class="text-secondary">Zum Übergabezeitpunkt waren keine offenen Forderungen gespeichert.</div>
            @endforelse
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header">Fotos und Dokumente</div>
        <div class="list-group list-group-flush">
            @forelse ($transition->documents as $document)
                <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3"
                   href="{{ route('tenant-transitions.documents.download', [$transition, $document]) }}">
                    <span>
                        <strong>{{ $document->original_name }}</strong>
                        <span class="d-block small text-secondary">{{ $document->pivot->category === 'photo' ? 'Übergabefoto' : 'Übergabedokument' }} · {{ $document->document_number }}</span>
                    </span>
                    <span>Herunterladen</span>
                </a>
            @empty
                <div class="card-body text-secondary">Keine Dateien hinterlegt.</div>
            @endforelse
        </div>
    </div>

    @if ($transition->notes)
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header">Interne Notizen</div>
            <div class="card-body">{!! nl2br(e($transition->notes)) !!}</div>
        </div>
    @endif
</div>
@endsection
