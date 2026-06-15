@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h2 mb-1">Pächterwechsel</h1>
            <p class="text-secondary mb-0">Abgeschlossene Übergaben mit Vertragsparteien, Zählerständen und Nachweisen.</p>
        </div>
        @can('create', App\Models\TenantTransition::class)
            <a class="btn btn-primary" href="{{ route('tenant-transitions.create') }}">Pächterwechsel durchführen</a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Übergabe</th>
                        <th>Parzelle</th>
                        <th>Bisherige Vertragsparteien</th>
                        <th>Neue Vertragsparteien</th>
                        <th>Abgeschlossen durch</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transitions as $transition)
                        <tr>
                            <td>{{ $transition->transfer_date->format('d.m.Y') }}</td>
                            <td>
                                <a href="{{ route('parcels.show', $transition->parcel) }}">
                                    {{ $transition->parcel->parcel_number }}
                                </a>
                            </td>
                            <td>{{ collect($transition->outgoing_members_snapshot)->map(fn ($member) => $member['first_name'].' '.$member['last_name'])->implode(', ') }}</td>
                            <td>{{ collect($transition->incoming_members_snapshot)->map(fn ($member) => $member['first_name'].' '.$member['last_name'])->implode(', ') }}</td>
                            <td>
                                {{ $transition->completer->name }}
                                <div class="small text-secondary">{{ $transition->completed_at->format('d.m.Y H:i') }}</div>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('tenant-transitions.show', $transition) }}">Details</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <strong>Noch kein Pächterwechsel durchgeführt.</strong>
                                <div class="text-secondary">Starte den geführten Übergabeprozess direkt hier oder in einer Parzelle.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($transitions->hasPages())
            <div class="card-footer">{{ $transitions->links() }}</div>
        @endif
    </div>
</div>
@endsection
