@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Mahnung {{ $notice->notice_number }}</h1>
            <p class="text-secondary mb-0">Mahnstufe {{ $notice->level }} · Rechnung {{ $notice->invoice_number }}</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-primary" href="{{ route('dunning-notices.pdf', $notice) }}">PDF herunterladen</a>
            <a class="btn btn-outline-secondary" href="{{ route('invoices.show', $notice->invoice) }}">Zur Rechnung</a>
        </div>
    </div>

    @if ($notice->status === App\Enums\DunningNoticeStatus::Cancelled)
        <div class="alert alert-warning">
            <strong>Storniert am {{ $notice->cancelled_at->format('d.m.Y H:i') }}</strong><br>
            {{ $notice->cancellation_reason }}
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">Forderung</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-7">Rechnungsbetrag</dt><dd class="col-5 text-end">{{ number_format((float) $notice->invoice_amount, 2, ',', '.') }} €</dd>
                        <dt class="col-7">Frühere aktive Gebühren</dt><dd class="col-5 text-end">{{ number_format((float) $notice->previous_fees_amount, 2, ',', '.') }} €</dd>
                        <dt class="col-7">Gebühr dieser Stufe</dt><dd class="col-5 text-end">{{ number_format((float) $notice->fee_amount, 2, ',', '.') }} €</dd>
                        <dt class="col-7 border-top pt-2">Gesamtforderung</dt><dd class="col-5 border-top pt-2 text-end fw-bold">{{ number_format((float) $notice->total_due, 2, ',', '.') }} €</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">Fristen und Status</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-6">Ausgestellt</dt><dd class="col-6">{{ $notice->issued_at->format('d.m.Y') }}</dd>
                        <dt class="col-6">Neue Frist</dt><dd class="col-6">{{ $notice->due_at->format('d.m.Y') }}</dd>
                        <dt class="col-6">Status</dt><dd class="col-6">{{ $notice->status->label() }}</dd>
                        <dt class="col-6">Erstellt von</dt><dd class="col-6">{{ $notice->creator->name }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    @if ($notice->note)
        <div class="card border-0 shadow-sm mt-4"><div class="card-body"><strong>Hinweis</strong><p class="mb-0 mt-2">{!! nl2br(e($notice->note)) !!}</p></div></div>
    @endif

    @if ($notice->status === App\Enums\DunningNoticeStatus::Issued)
        @can('cancel', $notice)
            <form method="POST" action="{{ route('dunning-notices.cancel', $notice) }}" class="card border-danger mt-4">
                @csrf
                @method('PATCH')
                <div class="card-body">
                    <h2 class="h5">Mahnung stornieren</h2>
                    <p class="text-secondary">Nur die aktuell höchste Mahnstufe kann storniert werden. Der Datensatz bleibt vollständig erhalten.</p>
                    <label class="form-label" for="cancellation_reason">Stornierungsgrund</label>
                    <textarea class="form-control" id="cancellation_reason" name="cancellation_reason" required minlength="5" maxlength="2000" rows="3">{{ old('cancellation_reason') }}</textarea>
                    <x-validation-errors />
                </div>
                <div class="card-footer bg-body border-0">
                    <button class="btn btn-outline-danger" onclick="return confirm('Diese Mahnung wirklich stornieren?')">Mahnung stornieren</button>
                </div>
            </form>
        @endcan
    @endif
</div>
@endsection
