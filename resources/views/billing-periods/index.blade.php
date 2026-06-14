@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">Abrechnungsperioden</h1>
        @can('create', App\Models\BillingPeriod::class)
            <a class="btn btn-primary" href="{{ route('billing-periods.create') }}">Periode anlegen</a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Bezeichnung</th>
                        <th>Zeitraum</th>
                        <th>Status</th>
                        <th>Rechnungen</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($periods as $period)
                        <tr>
                            <td>{{ $period->name }}</td>
                            <td>{{ $period->starts_at->format('d.m.Y') }} – {{ $period->ends_at->format('d.m.Y') }}</td>
                            <td>{{ $period->status->label() }}</td>
                            <td>{{ $period->invoices_count }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('billing-periods.show', $period) }}">Öffnen</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4">Keine Abrechnungsperioden vorhanden.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $periods->links() }}</div>
</div>
@endsection
