@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h2 mb-1">Parzelle {{ $parcel->parcel_number }}</h1>
            <span class="text-secondary">{{ $parcel->status->label() }} · {{ number_format((float) $parcel->area_sqm, 2, ',', '.') }} m²</span>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('parcel-map.index') }}">Im Lageplan</a>
            @can('update', $parcel)
                <a class="btn btn-primary" href="{{ route('parcels.edit', $parcel) }}">Bearbeiten</a>
            @endcan
            @can('create', App\Models\ParcelTenant::class)
                <a class="btn btn-outline-primary" href="{{ route('parcel-tenants.create', ['parcel_id' => $parcel->id]) }}">Pächter zuordnen</a>
            @endcan
            @can('create', App\Models\TenantTransition::class)
                @if ($parcel->tenancies->contains(fn ($tenancy) => $tenancy->is_primary
                    && $tenancy->starts_at->lte(today())
                    && ($tenancy->ends_at === null || $tenancy->ends_at->gte(today()))))
                    <a class="btn btn-outline-warning" href="{{ route('tenant-transitions.create', ['parcel_id' => $parcel->id]) }}">Pächterwechsel</a>
                @endif
            @endcan
            @if (App\Enums\FeatureModule::Meters->enabled())
            @can('create', App\Models\Meter::class)
                <a class="btn btn-outline-primary" href="{{ route('meters.create', ['parcel_id' => $parcel->id]) }}">Zähler anlegen</a>
            @endcan
            @endif
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
    @can('viewAny', App\Models\TenantTransition::class)
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header">Übergabehistorie</div>
            <div class="list-group list-group-flush">
                @forelse ($parcel->tenantTransitions as $transition)
                    <a class="list-group-item list-group-item-action d-flex flex-wrap justify-content-between gap-2"
                       href="{{ route('tenant-transitions.show', $transition) }}">
                        <span>
                            <strong>Übergabe am {{ $transition->transfer_date->format('d.m.Y') }}</strong>
                            <span class="d-block small text-secondary">
                                {{ collect($transition->outgoing_members_snapshot)->map(fn ($member) => $member['first_name'].' '.$member['last_name'])->implode(', ') }}
                                → {{ collect($transition->incoming_members_snapshot)->map(fn ($member) => $member['first_name'].' '.$member['last_name'])->implode(', ') }}
                            </span>
                        </span>
                        <span class="small text-secondary">{{ $transition->completer->name }}</span>
                    </a>
                @empty
                    <div class="card-body">
                        <strong>Noch kein Pächterwechsel historisiert.</strong>
                        <div class="text-secondary">Ein geführter Übergabeprozess erscheint nach dem Abschluss dauerhaft hier.</div>
                    </div>
                @endforelse
            </div>
        </div>
    @endcan
    @if (App\Enums\FeatureModule::Meters->enabled())
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
    @endif
    @if (App\Enums\FeatureModule::WorkHours->enabled())
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>Arbeitsstunden</span>
            @if (auth()->user()->hasTenantAccess())
                <a class="btn btn-sm btn-outline-success" href="{{ route('work-hour-submissions.create', ['parcel_id' => $parcel->id]) }}">
                    Arbeitsstunden melden
                </a>
            @endif
        </div>
        @if ($parcel->workHours->isEmpty())
            <div class="card-body">
                <strong>Noch kein Arbeitsstundenkonto vorhanden.</strong>
                <div class="text-secondary">Konten werden automatisch angelegt, sobald die Parzelle innerhalb einer Abrechnungsperiode verpachtet ist.</div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Abrechnungsperiode</th>
                            <th>Pflicht</th>
                            <th>Manuell</th>
                            <th>Arbeitseinsätze</th>
                            <th>Pächtermeldungen</th>
                            <th>Geleistet</th>
                            <th>Fehlend</th>
                            <th>Fehlbetrag</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($parcel->workHours as $workHour)
                            <tr>
                                <td>
                                    @can('view', $workHour->billingPeriod)
                                        <a href="{{ route('billing-periods.show', $workHour->billingPeriod) }}">
                                            {{ $workHour->billingPeriod->name }}
                                        </a>
                                    @else
                                        {{ $workHour->billingPeriod->name }}
                                    @endcan
                                    <div class="small text-secondary">
                                        {{ $workHour->billingPeriod->starts_at->format('d.m.Y') }}–{{ $workHour->billingPeriod->ends_at->format('d.m.Y') }}
                                        · {{ $workHour->billingPeriod->status->label() }}
                                    </div>
                                </td>
                                <td>
                                    {{ number_format((float) $workHour->hours_required, 2, ',', '.') }} Std.
                                    <div class="small text-secondary">
                                        {{ number_format((float) $workHour->occupancy_factor * 100, 2, ',', '.') }} % Belegung
                                        @if ($workHour->hours_required_overridden)
                                            · manuell
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @can('update', $workHour)
                                        <form class="d-flex align-items-center gap-2" method="POST" action="{{ route('work-hours.update', $workHour) }}">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="parcel_id" value="{{ $workHour->parcel_id }}">
                                            <input type="hidden" name="hours_required" value="{{ $workHour->hours_required }}">
                                            <input type="hidden" name="penalty_rate" value="{{ $workHour->penalty_rate }}">
                                            <input type="hidden" name="notes" value="{{ $workHour->notes }}">
                                            <input type="hidden" name="return_to" value="parcel">
                                            <input class="form-control form-control-sm"
                                                   aria-label="Manuell anerkannte Stunden für {{ $workHour->billingPeriod->name }}"
                                                   name="hours_done"
                                                   type="number"
                                                   min="0"
                                                   step="0.25"
                                                   value="{{ $workHour->manual_hours_done }}"
                                                   required
                                                   style="min-width: 6rem">
                                            <button class="btn btn-sm btn-primary">Speichern</button>
                                        </form>
                                    @else
                                        {{ number_format((float) $workHour->manual_hours_done, 2, ',', '.') }} Std.
                                    @endcan
                                </td>
                                <td>{{ number_format((float) $workHour->event_hours_done, 2, ',', '.') }} Std.</td>
                                <td>{{ number_format((float) $workHour->submission_hours_done, 2, ',', '.') }} Std.</td>
                                <td><strong>{{ number_format((float) $workHour->hours_done, 2, ',', '.') }} Std.</strong></td>
                                <td>{{ number_format((float) $workHour->hours_missing, 2, ',', '.') }} Std.</td>
                                <td>{{ number_format((float) $workHour->penalty_amount, 2, ',', '.') }} €</td>
                                <td class="text-end">
                                    @can('update', $workHour)
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('work-hours.edit', $workHour) }}">Details</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-secondary small">
                Arbeitseinsätze und bestätigte Pächtermeldungen werden automatisch addiert. Das Eingabefeld verändert nur zusätzlich manuell anerkannte Stunden.
            </div>
        @endif
    </div>
    @endif
</div>
@endsection
