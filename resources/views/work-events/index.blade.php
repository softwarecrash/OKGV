@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Arbeitseinsätze</h1>
            <p class="text-secondary mb-0">Termine, Teilnehmer und bestätigte Stunden verwalten.</p>
        </div>
        @can('create', App\Models\WorkEvent::class)
            @if ($editablePeriods->isNotEmpty())
                <div class="dropdown">
                    <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Arbeitseinsatz anlegen
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @foreach ($editablePeriods as $period)
                            <li>
                                <a class="dropdown-item" href="{{ route('billing-periods.work-events.create', $period) }}">
                                    {{ $period->name }}
                                    <span class="text-secondary">· {{ $period->status->label() }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <span class="text-secondary small">Keine bearbeitbare Abrechnungsperiode vorhanden.</span>
            @endif
        @endcan
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-md-5">
                    <label class="form-label" for="billing_period_id">Abrechnungsperiode</label>
                    <select class="form-select" id="billing_period_id" name="billing_period_id">
                        <option value="">Alle Perioden</option>
                        @foreach ($periods as $period)
                            <option value="{{ $period->id }}" @selected($selectedPeriodId === $period->id)>{{ $period->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Alle Status</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected($selectedStatus === $status)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-primary">Filtern</button>
                    <a class="btn btn-outline-secondary" href="{{ route('work-events.index') }}">Zurücksetzen</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Termin</th><th>Bezeichnung</th><th>Periode</th><th>Status</th><th>Teilnehmer</th><th></th></tr></thead>
                <tbody>
                    @forelse ($workEvents as $workEvent)
                        <tr>
                            <td>{{ $workEvent->starts_at->format('d.m.Y H:i') }}</td>
                            <td>{{ $workEvent->title }}</td>
                            <td>{{ $workEvent->billingPeriod->name }}</td>
                            <td>
                                {{ $workEvent->status->label() }}
                                @if ($workEvent->status === App\Enums\WorkEventStatus::Planned && $workEvent->ends_at->isPast())
                                    <span class="badge text-bg-warning">Bearbeitung fällig</span>
                                @endif
                            </td>
                            <td>{{ $workEvent->participants_count }}</td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('work-events.show', $workEvent) }}">Öffnen</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4"><strong>Noch keine Arbeitseinsätze angelegt.</strong><br><span class="text-secondary">Neue Termine werden innerhalb einer Abrechnungsperiode angelegt.</span></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($workEvents->hasPages())
            <div class="card-footer">{{ $workEvents->links() }}</div>
        @endif
    </div>
</div>
@endsection
