@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-1">Mahnstufe {{ $nextLevel }} erstellen</h1>
    <p class="text-secondary mb-4">Rechnung {{ $invoice->invoice_number }} · {{ $invoice->member->full_name }}</p>

    <div class="alert alert-warning">
        Nach dem Ausstellen sind Betrag, Frist, Empfänger und Text unveränderlich.
        Eine Korrektur ist nur durch begründete Stornierung und erneute Ausstellung möglich.
    </div>

    <form method="POST" action="{{ route('invoices.dunning-notices.store', $invoice) }}" class="card border-0 shadow-sm">
        @csrf
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-5">Offener Rechnungsbetrag</dt>
                <dd class="col-sm-7">{{ number_format((float) $invoice->total_amount, 2, ',', '.') }} €</dd>
                <dt class="col-sm-5">Bisherige aktive Mahngebühren</dt>
                <dd class="col-sm-7">{{ number_format($previousFees, 2, ',', '.') }} €</dd>
            </dl>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="fee_amount">Mahngebühr dieser Stufe</label>
                    <div class="input-group">
                        <input class="form-control" id="fee_amount" name="fee_amount" type="number" min="0" max="999999.99" step="0.01" required value="{{ old('fee_amount', '0.00') }}">
                        <span class="input-group-text">€</span>
                    </div>
                    <div class="form-text">Optional. Mit 0,00 € wird keine zusätzliche Gebühr berechnet.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="due_at">Neue Zahlungsfrist</label>
                    <input class="form-control" id="due_at" name="due_at" type="date" required min="{{ now()->addDay()->toDateString() }}" value="{{ old('due_at', now()->addDays(14)->toDateString()) }}">
                </div>
                <div class="col-12">
                    <label class="form-label" for="note">Zusätzlicher Hinweis</label>
                    <textarea class="form-control" id="note" name="note" rows="4" maxlength="2000">{{ old('note') }}</textarea>
                    <div class="form-text">Optionaler sachlicher Text für diese Mahnung. Keine internen Notizen eintragen.</div>
                </div>
            </div>

            <x-validation-errors />
        </div>
        <div class="card-footer bg-body border-0">
            <button class="btn btn-danger" onclick="return confirm('Mahnstufe {{ $nextLevel }} jetzt unveränderlich ausstellen?')">Mahnung ausstellen</button>
            <a class="btn btn-outline-secondary" href="{{ route('invoices.show', $invoice) }}">Abbrechen</a>
        </div>
    </form>
</div>
@endsection
