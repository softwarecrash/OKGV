@extends('layouts.app')
@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div><h1 class="h2 mb-1">Sammellastschrift</h1><span class="text-secondary">{{ $batch->message_id }} · {{ $batch->status->label() }}</span></div>
        <div class="d-flex flex-wrap gap-2">
            @can('export', $batch)<form method="POST" action="{{ route('payment-batches.export', $batch) }}">@csrf<button class="btn btn-primary">pain.008 XML herunterladen</button></form>@endcan
            @can('submit', $batch)<form method="POST" action="{{ route('payment-batches.submit', $batch) }}" onsubmit="return confirm('Bestätigen, dass diese XML-Datei bei der Bank eingereicht wurde?')">@csrf<button class="btn btn-success">Als eingereicht markieren</button></form>@endcan
            @can('settle', $batch)<form method="POST" action="{{ route('payment-batches.settle', $batch) }}" onsubmit="return confirm('Alle nicht zurückgegebenen Lastschriften als bezahlt markieren?')">@csrf<button class="btn btn-outline-success">Als gebucht markieren</button></form>@endcan
        </div>
    </div>
    <div class="alert alert-info">Der XML-Export enthält verschlüsselt gespeicherte Bankdaten und darf nur über einen sicheren Bankzugang weitergegeben werden. Der SHA-256-Wert dokumentiert den exportierten Inhalt.</div>
    <div class="card border-0 shadow-sm mb-4"><div class="card-body"><dl class="row mb-0">
        <dt class="col-sm-3">Einzugstag</dt><dd class="col-sm-9">{{ $batch->requested_collection_date->format('d.m.Y') }}</dd>
        <dt class="col-sm-3">Anzahl / Summe</dt><dd class="col-sm-9">{{ $batch->item_count }} / {{ number_format((float) $batch->control_sum, 2, ',', '.') }} €</dd>
        <dt class="col-sm-3">Export-Prüfsumme</dt><dd class="col-sm-9"><code>{{ $batch->xml_sha256 ?? 'Noch nicht exportiert' }}</code></dd>
    </dl></div></div>
    <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th>Rechnung</th><th>Mitglied</th><th>Betrag</th><th>Sequenz</th><th>Status</th><th>Rückgabe</th><th></th></tr></thead>
        <tbody>
        @foreach ($batch->items as $item)
            <tr>
                <td>{{ $item->invoice->invoice_number }}</td><td>{{ $item->invoice->member->full_name }}</td><td>{{ number_format((float) $item->amount, 2, ',', '.') }} €</td><td>{{ $item->sequence_type }}</td><td>{{ $item->status->label() }}</td>
                <td>{{ $item->return_reason_code ? $item->return_reason_code.' · '.$item->return_reason_text : '–' }}</td>
                <td class="text-end">@can('return', $item)<a class="btn btn-sm btn-outline-danger" href="{{ route('payment-returns.create', $item) }}">Rücklastschrift</a>@endcan</td>
            </tr>
        @endforeach
        </tbody>
    </table></div></div>
</div>
@endsection
