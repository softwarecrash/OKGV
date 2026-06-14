@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h2 mb-1">Parzelle {{ $parcel->parcel_number }}</h1>
            <span class="text-secondary">{{ $parcel->status->label() }} · {{ number_format((float) $parcel->area_sqm, 2, ',', '.') }} m²</span>
        </div>
        <div class="d-flex gap-2">
            @can('update', $parcel)
                <a class="btn btn-primary" href="{{ route('parcels.edit', $parcel) }}">Bearbeiten</a>
            @endcan
            @can('create', App\Models\ParcelTenant::class)
                <a class="btn btn-outline-primary" href="{{ route('parcel-tenants.create', ['parcel_id' => $parcel->id]) }}">Pächter zuordnen</a>
            @endcan
            @can('create', App\Models\Meter::class)
                <a class="btn btn-outline-primary" href="{{ route('meters.create', ['parcel_id' => $parcel->id]) }}">Zähler anlegen</a>
            @endcan
        </div>
    </div>
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header">Parzellendaten</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Lage</dt><dd class="col-sm-7">{{ $parcel->location_description ?: '–' }}</dd>
                        <dt class="col-sm-5">Status</dt><dd class="col-sm-7">{{ $parcel->status->label() }}</dd>
                    </dl>
                    @if (auth()->user()->canViewAllMasterData())
                        <hr>
                        <strong>Interne Notizen</strong>
                        <p class="mb-0 mt-2">{!! nl2br(e($parcel->notes ?: 'Keine Notizen.')) !!}</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header">Pächterhistorie</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Mitglied</th><th>Zeitraum</th><th>Rolle</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($parcel->tenancies as $tenancy)
                                <tr>
                                    <td><a href="{{ route('members.show', $tenancy->member) }}">{{ $tenancy->member->full_name }}</a></td>
                                    <td>{{ $tenancy->starts_at->format('d.m.Y') }} – {{ $tenancy->ends_at?->format('d.m.Y') ?? 'heute' }}</td>
                                    <td>{{ $tenancy->is_primary ? 'Hauptpächter' : 'Mitpächter' }}</td>
                                    <td class="text-end">
                                        @can('update', $tenancy)
                                            <a class="btn btn-sm btn-outline-primary" href="{{ route('parcel-tenants.edit', $tenancy) }}">Bearbeiten</a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center py-4"><strong>Noch keine Vertragspartei zugeordnet.</strong><br><span class="text-secondary">Lege für jede im Pachtvertrag genannte Person eine eigene Zuordnung an.</span></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header">Zähler</div>
        <div class="list-group list-group-flush">
            @forelse ($parcel->meters()->latest('installed_at')->get() as $meter)
                <a class="list-group-item list-group-item-action" href="{{ route('meters.show', $meter) }}">
                    {{ $meter->type->label() }} · {{ $meter->meter_number }}
                    <span class="text-secondary">({{ $meter->status->label() }})</span>
                </a>
            @empty
                <div class="card-body"><strong>Noch keine Zähler vorhanden.</strong><div class="text-secondary">Zähler werden mit Einbaudatum und Startstand angelegt.</div></div>
            @endforelse
        </div>
    </div>
</div>
@endsection
