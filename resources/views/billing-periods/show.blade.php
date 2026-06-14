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
            @if ($billingPeriod->isEditable())
                @can('update', $billingPeriod)
                    <a class="btn btn-outline-primary" href="{{ route('billing-periods.edit', $billingPeriod) }}">Bearbeiten</a>
                    <a class="btn btn-primary" href="{{ route('billing-periods.billing-rates.create', $billingPeriod) }}">Preis aus Vorlage</a>
                    <a class="btn btn-outline-primary" href="{{ route('billing-periods.work-hours.create', $billingPeriod) }}">Arbeitsstunden erfassen</a>
                    @can('create', App\Models\WorkEvent::class)
                        <a class="btn btn-outline-primary" href="{{ route('billing-periods.work-events.create', $billingPeriod) }}">Arbeitseinsatz anlegen</a>
                    @endcan
                @endcan
                @can('calculate', $billingPeriod)
                    <form method="POST" action="{{ route('billing-periods.calculate', $billingPeriod) }}" onsubmit="return confirm('Abrechnung jetzt neu berechnen? Vorhandene Entwürfe dieser Periode werden durch die aktuelle Berechnung ersetzt.')">
                        @csrf
                        <button class="btn btn-success">
                            {{ $billingPeriod->status === App\Enums\BillingPeriodStatus::Calculated ? 'Zwischenstand neu berechnen' : 'Zwischenstand berechnen' }}
                        </button>
                    </form>
                @endcan
            @endif
            @if ($billingPeriod->status === App\Enums\BillingPeriodStatus::Calculated)
                @can('approve', $billingPeriod)
                    <form method="POST" action="{{ route('billing-periods.approve', $billingPeriod) }}" onsubmit="return confirm('Alle Rechnungen dieser Periode freigeben? Freigegebene Rechnungen und Positionen können anschließend nicht mehr verändert werden.')">
                        @csrf
                        <button class="btn btn-success">Rechnungen freigeben</button>
                    </form>
                @endcan
            @endif
            @if ($billingPeriod->status === App\Enums\BillingPeriodStatus::Approved)
                @can('archive', $billingPeriod)
                    <form method="POST" action="{{ route('billing-periods.archive', $billingPeriod) }}" onsubmit="return confirm('Abrechnungsperiode archivieren? Die Daten bleiben erhalten, die Periode gilt danach als abgeschlossen.')">
                        @csrf
                        <button class="btn btn-outline-secondary">Archivieren</button>
                    </form>
                @endcan
            @endif
        </div>
    </div>

    @if ($billingPeriod->status === App\Enums\BillingPeriodStatus::Draft)
        <div class="alert alert-info">
            Preise und Zuordnungen können noch geändert werden. „Zwischenstand berechnen“ erzeugt prüfbare Rechnungsentwürfe und ist beliebig oft wiederholbar.
        </div>
    @elseif ($billingPeriod->status === App\Enums\BillingPeriodStatus::Calculated)
        <div class="alert alert-warning">
            <strong>Prüfbarer Zwischenstand:</strong> Die Rechnungen sind noch nicht endgültig. Eine Änderung an Zeitraum, Preisen oder Zuordnungen verwirft diese Entwürfe automatisch; danach kann neu berechnet werden. Erst „Rechnungen freigeben“ ist endgültig.
        </div>
    @endif

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
                                @if ($billingPeriod->isEditable())
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
                                                <form method="POST" action="{{ route('billing-rate-assignments.destroy', $assignment) }}" onsubmit="return confirm('Diese Preiszuordnung entfernen? Sie wird bei der nächsten Berechnung nicht mehr berücksichtigt.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger">Entfernen</button>
                                                </form>
                                            @endcan
                                        </div>
                                    @empty
                                        <p class="text-secondary mb-0 mt-2">Noch keine Zuordnung. Dieser Preis erzeugt erst eine Position, nachdem unten ein Mitglied oder eine Parzelle gewählt wurde.</p>
                                    @endforelse

                                    @if ($billingPeriod->isEditable())
                                        <form class="row g-2 mt-2" method="POST" action="{{ route('billing-rate-assignments.store', $rate) }}">
                                            @csrf
                                            <div class="col-md-4">
                                                <label class="form-label" for="member-{{ $rate->id }}">Mitglied</label>
                                                <select class="form-select" id="member-{{ $rate->id }}" name="member_id">
                                                    <option value="">Mitglied auswählen</option>
                                                    @foreach ($members as $member)
                                                        <option value="{{ $member->id }}">{{ $member->last_name }}, {{ $member->first_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label" for="parcel-{{ $rate->id }}">Oder Parzelle</label>
                                                <select class="form-select" id="parcel-{{ $rate->id }}" name="parcel_id">
                                                    <option value="">oder Parzelle</option>
                                                    @foreach ($parcels as $parcel)
                                                        <option value="{{ $parcel->id }}">{{ $parcel->parcel_number }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label" for="quantity-{{ $rate->id }}">Menge</label>
                                                <input class="form-control" id="quantity-{{ $rate->id }}" name="quantity" type="number" min="0.0001" step="0.0001" value="1" required>
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
                        <tr><td colspan="6" class="text-center py-4"><strong>Noch keine Preise angelegt.</strong><br><span class="text-secondary">Lege zuerst alle benötigten Kostenarten an, bevor du die Abrechnung berechnest.</span></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>Arbeitsstunden</span>
            <span class="text-secondary small">Fehlstunden werden beim Berechnen automatisch als Rechnungsposition übernommen.</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Mitglied</th>
                        <th>Pflicht</th>
                        <th>Manuell</th>
                        <th>Aus Einsätzen</th>
                        <th>Gesamt</th>
                        <th>Fehlend</th>
                        <th>Je Fehlstunde</th>
                        <th>Strafzahlung</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($billingPeriod->workHours->sortBy(fn ($entry) => $entry->member->last_name) as $workHour)
                        <tr>
                            <td>{{ $workHour->member->full_name }}</td>
                            <td>{{ number_format((float) $workHour->hours_required, 2, ',', '.') }} Std.</td>
                            <td>{{ number_format((float) $workHour->manual_hours_done, 2, ',', '.') }} Std.</td>
                            <td>{{ number_format((float) $workHour->event_hours_done, 2, ',', '.') }} Std.</td>
                            <td>{{ number_format((float) $workHour->hours_done, 2, ',', '.') }} Std.</td>
                            <td>
                                @if ((float) $workHour->hours_missing > 0)
                                    <span class="badge text-bg-warning">{{ number_format((float) $workHour->hours_missing, 2, ',', '.') }} Std.</span>
                                @else
                                    <span class="text-success">Erfüllt</span>
                                @endif
                            </td>
                            <td>{{ number_format((float) $workHour->penalty_rate, 2, ',', '.') }} €</td>
                            <td>{{ number_format((float) $workHour->penalty_amount, 2, ',', '.') }} €</td>
                            <td class="text-end">
                                @can('update', $workHour)
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('work-hours.edit', $workHour) }}">Bearbeiten</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <strong>Noch keine Arbeitsstunden erfasst.</strong><br>
                                <span class="text-secondary">Ohne Arbeitsstundenkonto entsteht für ein Mitglied keine Fehlstundenposition.</span>
                            </td>
                        </tr>
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
                        <tr><td colspan="5" class="text-center py-4"><strong>Noch keine Rechnungen berechnet.</strong><br><span class="text-secondary">Im Entwurf kannst du Preise prüfen und anschließend über „Zwischenstand berechnen“ Rechnungsvorschläge erzeugen.</span></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
