@extends('layouts.app')
@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">SEPA-Mandate</h1>
        <a class="btn btn-primary" href="{{ route('sepa-mandates.create') }}">Mandat anlegen</a>
    </div>
    <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th>Mitglied</th><th>Referenz</th><th>Konto</th><th>Gültigkeit</th><th>Status</th><th>Herkunft</th><th></th></tr></thead>
        <tbody>
        @forelse ($mandates as $mandate)
            <tr>
                <td>{{ $mandate->member->full_name }}</td>
                <td>{{ $mandate->mandate_reference }}</td>
                <td>{{ $mandate->masked_iban }}</td>
                <td>{{ $mandate->valid_from->format('d.m.Y') }} bis {{ $mandate->valid_until?->format('d.m.Y') ?? 'offen' }}</td>
                <td>{{ $mandate->status->label() }} · {{ $mandate->mandate_type->label() }}</td>
                <td>
                    @if ($mandate->created_by)
                        Selbst hinterlegt
                    @else
                        Verwaltung
                    @endif
                    @if ($mandate->revoked_at)
                        <div class="small text-secondary">Widerrufen am {{ $mandate->revoked_at->format('d.m.Y H:i') }}</div>
                    @endif
                </td>
                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('sepa-mandates.edit', $mandate) }}">Bearbeiten</a></td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center py-4"><strong>Noch keine SEPA-Mandate.</strong><br><span class="text-secondary">Ein Mandat wird benötigt, bevor eine Rechnung per Lastschrift eingezogen werden kann.</span></td></tr>
        @endforelse
        </tbody>
    </table></div></div>
    <div class="mt-3">{{ $mandates->links() }}</div>
</div>
@endsection
