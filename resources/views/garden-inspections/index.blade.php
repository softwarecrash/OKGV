@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h1 class="h2 mb-1">Gartenbegehungen</h1>
                <p class="text-secondary mb-0">Prüfungen und Fristen zu Parzellen. Pächter sehen nur Hinweise zu eigenen Parzellen.</p>
            </div>
            @can('create', App\Models\GardenInspection::class)
                <a href="{{ route('garden-inspections.create') }}" class="btn btn-primary">Begehung anlegen</a>
            @endcan
        </div>

        @forelse ($rows as $row)
            <article class="card card-body mb-3">
                <a class="h5 mb-1" href="{{ route('garden-inspections.show', $row) }}">{{ $row->title }}</a>
                <span class="text-secondary">
                    {{ $row->inspected_at->format('d.m.Y H:i') }}
                    @if ($row->finalized_at)
                        · Abgeschlossen
                    @endif
                </span>
            </article>
        @empty
            <div class="alert alert-info">Noch keine Gartenbegehung erfasst.</div>
        @endforelse

        {{ $rows->links() }}
    </div>
@endsection
