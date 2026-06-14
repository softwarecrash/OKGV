@extends('layouts.app')
@section('content')
<div class="container">
    <h1 class="h2 mb-4">Sammellastschrift vorbereiten</h1>
    @unless ($settingsReady)
        <div class="alert alert-danger">Die SEPA-Einstellungen des Vereins fehlen. <a href="{{ route('sepa-settings.edit') }}">Jetzt hinterlegen</a>.</div>
    @endunless
    <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('payment-batches.store') }}">
        @csrf
        <x-validation-errors />
        <div class="alert alert-info">Es werden nur freigegebene, offene Rechnungen eingezogen. Für jedes Mitglied muss am gewählten Einzugstag ein aktives Mandat vorhanden sein.</div>
        <div class="mb-4">
            <label class="form-label" for="requested_collection_date">Gewünschter Einzugstag</label>
            <input class="form-control" style="max-width: 18rem" type="date" id="requested_collection_date" name="requested_collection_date" min="{{ now()->format('Y-m-d') }}" value="{{ old('requested_collection_date', now()->addDays(7)->format('Y-m-d')) }}" required>
            <div class="form-text">Plane ausreichend Vorlauf gemäß Vereinbarung mit deinem Kreditinstitut ein.</div>
        </div>
        <div class="table-responsive"><table class="table align-middle">
            <thead><tr><th>Auswahl</th><th>Rechnung</th><th>Mitglied</th><th>Fällig</th><th>Betrag</th><th>Mandat</th></tr></thead>
            <tbody>
            @forelse ($invoices as $invoice)
                @php($mandate = $invoice->member->sepaMandates->filter(fn ($entry) => $entry->status === App\Enums\SepaMandateStatus::Active)->sortByDesc('valid_from')->first())
                <tr>
                    <td><input class="form-check-input" type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}" @checked(in_array($invoice->id, old('invoice_ids', []))) @disabled(! $mandate)></td>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td>{{ $invoice->member->full_name }}</td>
                    <td>{{ $invoice->due_at->format('d.m.Y') }}</td>
                    <td>{{ number_format((float) $invoice->total_amount, 2, ',', '.') }} €</td>
                    <td>{{ $mandate ? $mandate->mandate_reference.' · '.$mandate->masked_iban : 'Kein aktives Mandat' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center py-4">Keine offenen, freigegebenen Rechnungen vorhanden.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="d-flex gap-2"><button class="btn btn-primary" @disabled(! $settingsReady)>Sammler erstellen</button><a class="btn btn-outline-secondary" href="{{ route('payment-batches.index') }}">Abbrechen</a></div>
    </form>
</div>
@endsection
