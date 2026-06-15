@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="h2 mb-1">Rechnung {{ $invoice->invoice_number }}</h1>
            <span class="text-secondary">{{ $invoice->status->label() }} · {{ $invoice->billingPeriod->name }}</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if (auth()->user()->canManageBilling() && $invoice->canReceivePaymentReminder())
                @php($latestActiveNotice = $invoice->dunningNotices->firstWhere('status', App\Enums\DunningNoticeStatus::Issued))
                @if (! $latestActiveNotice || ($latestActiveNotice->level < 3 && $latestActiveNotice->due_at->isPast()))
                    <a class="btn btn-outline-danger" href="{{ route('invoices.dunning-notices.create', $invoice) }}">
                        {{ $latestActiveNotice ? 'Nächste Mahnstufe erstellen' : 'Erste Mahnung erstellen' }}
                    </a>
                @endif
            @endif
            @can('reminder', $invoice)
                <a class="btn btn-outline-primary" href="{{ route('invoices.payment-reminder', $invoice) }}">Zahlungserinnerung als PDF</a>
            @endcan
            <a class="btn btn-primary" href="{{ route('invoices.pdf', $invoice) }}">PDF herunterladen</a>
        </div>
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
                        <dt class="col-6">Zahlungsstatus</dt><dd class="col-6">{{ $invoice->payment_status->label() }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    @if ($invoice->status === App\Enums\InvoiceStatus::Draft)
        <div class="alert alert-warning"><strong>Noch nicht freigegeben:</strong> Dieser Zwischenstand kann beliebig oft neu berechnet oder durch Änderungen an Preisen und Zuordnungen verworfen werden. Er darf noch nicht als endgültige Rechnung versendet werden.</div>
    @endif

    @if ($invoice->status === App\Enums\InvoiceStatus::Approved && $invoice->due_at->isPast() && in_array($invoice->payment_status, [App\Enums\InvoicePaymentStatus::Open, App\Enums\InvoicePaymentStatus::Returned], true))
        <div class="alert alert-info">
            Die Rechnung ist fällig und noch offen. Berechtigte Finanzkonten können eine sachliche Zahlungserinnerung ohne Mahnstufe oder Mahngebühr erzeugen.
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Position</th><th class="text-end">Menge</th><th class="text-end">Einzelpreis</th><th class="text-end">Gesamt</th></tr></thead>
                <tbody>
                    @foreach ($invoice->items as $item)
                        <tr>
                            <td>
                                {{ $item->description }}
                                @if (isset($item->metadata['settlement_type']))
                                    <div class="small text-secondary">
                                        {{ App\Enums\BillingSettlementType::from($item->metadata['settlement_type'])->label() }}
                                        @if ($item->metadata['prorated'] ?? false)
                                            · zeitanteilig {{ number_format((float) $item->metadata['proration_factor'] * 100, 2, ',', '.') }} %
                                        @endif
                                    </div>
                                @endif
                            </td>
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

    @if ($invoice->dunningNotices->isNotEmpty())
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header"><h2 class="h5 mb-0">Mahnhistorie</h2></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Mahnung</th><th>Stufe</th><th>Frist</th><th>Gebühr</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($invoice->dunningNotices as $notice)
                            @can('view', $notice)
                                <tr>
                                    <td>{{ $notice->notice_number }}<br><small class="text-secondary">{{ $notice->issued_at->format('d.m.Y') }}</small></td>
                                    <td>{{ $notice->level }}</td>
                                    <td>{{ $notice->due_at->format('d.m.Y') }}</td>
                                    <td>{{ number_format((float) $notice->fee_amount, 2, ',', '.') }} €</td>
                                    <td>{{ $notice->status->label() }}</td>
                                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('dunning-notices.show', $notice) }}">Anzeigen</a></td>
                                </tr>
                            @endcan
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
