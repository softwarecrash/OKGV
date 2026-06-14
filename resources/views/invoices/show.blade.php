@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="h2 mb-1">Rechnung {{ $invoice->invoice_number }}</h1>
            <span class="text-secondary">{{ $invoice->status->label() }} · {{ $invoice->billingPeriod->name }}</span>
        </div>
        <a class="btn btn-primary" href="{{ route('invoices.pdf', $invoice) }}">PDF herunterladen</a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">Empfänger</div>
                <div class="card-body">
                    @foreach ($invoice->recipients as $recipient)
                        <strong>{{ $recipient->full_name }}</strong>@if (! $loop->last)<br>@endif
                    @endforeach
                    @php($primaryRecipient = $invoice->primaryRecipient())
                    @if ($primaryRecipient)
                        <br>{{ $primaryRecipient->street }}<br>
                        {{ $primaryRecipient->zip }} {{ $primaryRecipient->city }}
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">Rechnungsdaten</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-6">Rechnungsdatum</dt><dd class="col-6">{{ $invoice->issued_at->format('d.m.Y') }}</dd>
                        <dt class="col-6">Fällig am</dt><dd class="col-6">{{ $invoice->due_at->format('d.m.Y') }}</dd>
                        <dt class="col-6">Status</dt><dd class="col-6">{{ $invoice->status->label() }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    @if ($invoice->status === App\Enums\InvoiceStatus::Draft)
        <div class="alert alert-warning">Dieser Rechnungsentwurf ist noch nicht freigegeben.</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Position</th><th class="text-end">Menge</th><th class="text-end">Einzelpreis</th><th class="text-end">Gesamt</th></tr></thead>
                <tbody>
                    @foreach ($invoice->items as $item)
                        <tr>
                            <td>{{ $item->description }}</td>
                            <td class="text-end">{{ number_format((float) $item->quantity, 4, ',', '.') }}</td>
                            <td class="text-end">{{ number_format((float) $item->unit_price, 4, ',', '.') }} €</td>
                            <td class="text-end">{{ number_format((float) $item->total_amount, 2, ',', '.') }} €</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-bold"><td colspan="3">Gesamtbetrag</td><td class="text-end">{{ number_format((float) $invoice->total_amount, 2, ',', '.') }} €</td></tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
