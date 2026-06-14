@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Arbeitsstunden</h1>
            <p class="text-secondary mb-0">Pflichtstunden, geleistete Stunden und mögliche Strafzahlungen je Abrechnungsperiode.</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-md-8">
                    <label class="form-label" for="billing_period_id">Abrechnungsperiode</label>
                    <select class="form-select" id="billing_period_id" name="billing_period_id">
                        <option value="">Alle Perioden</option>
                        @foreach ($periods as $period)
                            <option value="{{ $period->id }}" @selected($selectedPeriodId === $period->id)>
                                {{ $period->name }} · {{ $period->status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-primary">Filtern</button>
                    <a class="btn btn-outline-secondary" href="{{ route('work-hours.index') }}">Zurücksetzen</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Periode</th>
                        <th>Parzelle</th>
                        <th>Pflicht</th>
                        <th>Manuell</th>
                        <th>Einsätze</th>
                        <th>Gesamt</th>
                        <th>Fehlend</th>
                        <th>Strafzahlung</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($workHours as $workHour)
                        <tr>
                            <td>
                                <a href="{{ route('billing-periods.show', $workHour->billingPeriod) }}">
                                    {{ $workHour->billingPeriod->name }}
                                </a>
                            </td>
                            <td>Parzelle {{ $workHour->parcel->parcel_number }}</td>
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
                                <span class="text-secondary">Arbeitsstundenkonten werden gesammelt oder einzeln in einer Abrechnungsperiode angelegt.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($workHours->hasPages())
            <div class="card-footer">{{ $workHours->links() }}</div>
        @endif
    </div>
</div>
@endsection
