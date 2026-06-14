@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Mahnwesen</h1>
            <p class="text-secondary mb-0">Ausgestellte Mahnstufen, Fristen und Gebühren nachvollziehen.</p>
        </div>
        <a class="btn btn-outline-primary" href="{{ route('invoices.index') }}">
            {{ $eligibleInvoices }} überfällige {{ $eligibleInvoices === 1 ? 'Rechnung' : 'Rechnungen' }} prüfen
        </a>
    </div>

    <div class="alert alert-info">
        Mahnungen werden direkt an einer überfälligen Rechnung erstellt. Jede ausgestellte Stufe bleibt unveränderlich erhalten.
    </div>

    <form method="GET" class="card border-0 shadow-sm mb-4">
        <div class="card-body row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Alle Status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-8">
                <button class="btn btn-outline-primary">Filtern</button>
                <a class="btn btn-outline-secondary" href="{{ route('dunning-notices.index') }}">Zurücksetzen</a>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Mahnung</th><th>Rechnung</th><th>Mitglied</th><th>Stufe</th><th>Frist</th><th>Gesamtforderung</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($notices as $notice)
                        <tr>
                            <td>{{ $notice->notice_number }}<br><small class="text-secondary">{{ $notice->issued_at->format('d.m.Y') }}</small></td>
                            <td>{{ $notice->invoice_number }}</td>
                            <td>{{ $notice->invoice->member->full_name }}</td>
                            <td>{{ $notice->level }}</td>
                            <td>{{ $notice->due_at->format('d.m.Y') }}</td>
                            <td>{{ number_format((float) $notice->total_due, 2, ',', '.') }} €</td>
                            <td>{{ $notice->status->label() }}</td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('dunning-notices.show', $notice) }}">Details</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-4"><strong>Noch keine Mahnungen vorhanden.</strong><br><span class="text-secondary">Öffne eine überfällige Rechnung, um die erste Mahnstufe auszustellen.</span></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $notices->links() }}</div>
</div>
@endsection
