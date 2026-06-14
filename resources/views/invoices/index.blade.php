@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-4">Rechnungen</h1>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Nummer</th><th>Mitglied</th><th>Periode</th><th>Rechnung</th><th>Zahlung</th><th>Gesamt</th><th></th></tr></thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td>{{ $invoice->invoice_number }}</td>
                            <td>{{ $invoice->member->full_name }}</td>
                            <td>{{ $invoice->billingPeriod->name }}</td>
                            <td>{{ $invoice->status->label() }}</td>
                            <td>{{ $invoice->payment_status->label() }}</td>
                            <td>{{ number_format((float) $invoice->total_amount, 2, ',', '.') }} €</td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('invoices.show', $invoice) }}">Öffnen</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4"><strong>Noch keine Rechnungen vorhanden.</strong><br><span class="text-secondary">Rechnungen entstehen durch die Berechnung einer Abrechnungsperiode.</span></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $invoices->links() }}</div>
</div>
@endsection
