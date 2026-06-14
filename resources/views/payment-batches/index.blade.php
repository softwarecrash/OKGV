@extends('layouts.app')
@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4"><h1 class="h2 mb-0">Sammellastschriften</h1><a class="btn btn-primary" href="{{ route('payment-batches.create') }}">Sammler vorbereiten</a></div>
    <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th>Nachrichten-ID</th><th>Einzugstag</th><th>Posten</th><th>Summe</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($batches as $batch)
            <tr><td>{{ $batch->message_id }}</td><td>{{ $batch->requested_collection_date->format('d.m.Y') }}</td><td>{{ $batch->item_count }}</td><td>{{ number_format((float) $batch->control_sum, 2, ',', '.') }} €</td><td>{{ $batch->status->label() }}</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('payment-batches.show', $batch) }}">Öffnen</a></td></tr>
        @empty
            <tr><td colspan="6" class="text-center py-4"><strong>Noch keine Sammellastschrift.</strong><br><span class="text-secondary">Bereite aus freigegebenen, offenen Rechnungen den ersten pain.008-Export vor.</span></td></tr>
        @endforelse
        </tbody>
    </table></div></div>
    <div class="mt-3">{{ $batches->links() }}</div>
</div>
@endsection
