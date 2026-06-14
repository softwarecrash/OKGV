@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">{{ $billingPeriod->name }}</h1>
            <span class="text-secondary">
                {{ $billingPeriod->starts_at->format('d.m.Y') }} – {{ $billingPeriod->ends_at->format('d.m.Y') }}
                · {{ $billingPeriod->status->label() }}
            </span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($billingPeriod->isMutable())
                @can('update', $billingPeriod)
                    <a class="btn btn-outline-primary" href="{{ route('billing-periods.edit', $billingPeriod) }}">Bearbeiten</a>
                    <a class="btn btn-primary" href="{{ route('billing-periods.billing-rates.create', $billingPeriod) }}">Preis anlegen</a>
                @endcan
                @can('calculate', $billingPeriod)
                    <form method="POST" action="{{ route('billing-periods.calculate', $billingPeriod) }}">
                        @csrf
                        <button class="btn btn-success">Abrechnung berechnen</button>
                    </form>
                @endcan
            @endif
            @if ($billingPeriod->status === App\Enums\BillingPeriodStatus::Calculated)
                @can('approve', $billingPeriod)
                    <form method="POST" action="{{ route('billing-periods.approve', $billingPeriod) }}">
                        @csrf
                        <button class="btn btn-success">Rechnungen freigeben</button>
                    </form>
                @endcan
            @endif
            @if ($billingPeriod->status === App\Enums\BillingPeriodStatus::Approved)
                @can('archive', $billingPeriod)
                    <form method="POST" action="{{ route('billing-periods.archive', $billingPeriod) }}">
                        @csrf
                        <button class="btn btn-outline-secondary">Archivieren</button>
                    </form>
                @endcan
            @endif
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header">Preise</div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Code</th><th>Bezeichnung</th><th>Berechnung</th><th>Geltung</th><th>Betrag</th><th></th></tr></thead>
                <tbody>
                    @forelse ($billingPeriod->rates as $rate)
                        <tr>
                            <td><code>{{ $rate->code }}</code></td>
                            <td>{{ $rate->name }} @unless($rate->is_active)<span class="badge text-bg-secondary">inaktiv</span>@endunless</td>
                            <td>{{ $rate->calculation_type->label() }}</td>
                            <td>{{ $rate->scope->label() }}</td>
                            <td>{{ number_format((float) $rate->amount, 4, ',', '.') }} €</td>
                            <td class="text-end">
                                @if ($billingPeriod->isMutable())
                                    @can('update', $rate)
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('billing-periods.billing-rates.edit', [$billingPeriod, $rate]) }}">Bearbeiten</a>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                        @if ($rate->scope === App\Enums\BillingRateScope::Assignment)
                            <tr>
                                <td colspan="6" class="bg-body-tertiary">
                                    <strong>Zuordnungen</strong>
                                    @forelse ($rate->assignments as $assignment)
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <span>
                                                {{ $assignment->member?->full_name ?? 'Parzelle '.$assignment->parcel?->parcel_number }}
                                                · Menge {{ number_format((float) $assignment->quantity, 4, ',', '.') }}
                                            </span>
                                            @can('delete', $assignment)
                                                <form method="POST" action="{{ route('billing-rate-assignments.destroy', $assignment) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger">Entfernen</button>
                                                </form>
                                            @endcan
                                        </div>
                                    @empty
                                        <p class="text-secondary mb-0 mt-2">Noch keine Zuordnung.</p>
                                    @endforelse

                                    @if ($billingPeriod->isMutable())
                                        <form class="row g-2 mt-2" method="POST" action="{{ route('billing-rate-assignments.store', $rate) }}">
                                            @csrf
                                            <div class="col-md-4">
                                                <select class="form-select" name="member_id">
                                                    <option value="">Mitglied auswählen</option>
                                                    @foreach ($members as $member)
                                                        <option value="{{ $member->id }}">{{ $member->last_name }}, {{ $member->first_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <select class="form-select" name="parcel_id">
                                                    <option value="">oder Parzelle</option>
                                                    @foreach ($parcels as $parcel)
                                                        <option value="{{ $parcel->id }}">{{ $parcel->parcel_number }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-control" name="quantity" type="number" min="0.0001" step="0.0001" value="1" required aria-label="Menge">
                                            </div>
                                            <div class="col-md-2">
                                                <button class="btn btn-outline-primary w-100">Zuordnen</button>
                                            </div>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="6" class="text-center py-4">Noch keine Preise angelegt.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header">Rechnungen</div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Nummer</th><th>Mitglied</th><th>Status</th><th>Gesamt</th><th></th></tr></thead>
                <tbody>
                    @forelse ($billingPeriod->invoices as $invoice)
                        <tr>
                            <td>{{ $invoice->invoice_number }}</td>
                            <td>{{ $invoice->member->full_name }}</td>
                            <td>{{ $invoice->status->label() }}</td>
                            <td>{{ number_format((float) $invoice->total_amount, 2, ',', '.') }} €</td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('invoices.show', $invoice) }}">Öffnen</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4">Noch keine Rechnungen berechnet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
