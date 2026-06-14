@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h2 mb-0">Parzellen</h1>
        @can('create', App\Models\Parcel::class)
            <a class="btn btn-primary" href="{{ route('parcels.create') }}">Parzelle anlegen</a>
        @endcan
    </div>
    <form class="card card-body border-0 shadow-sm mb-4" method="GET">
        <div class="row g-2">
            <div class="col-md-7">
                <label class="form-label" for="q">Suche</label>
                <input class="form-control" id="q" name="q" value="{{ request('q') }}" placeholder="Parzellennummer oder Lage">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Alle</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-outline-primary w-100" type="submit">Filtern</button>
            </div>
        </div>
    </form>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Nummer</th><th>Fläche</th><th>Status</th><th>Lage</th><th class="text-end">Aktion</th></tr></thead>
                <tbody>
                    @forelse ($parcels as $parcel)
                        <tr>
                            <td>{{ $parcel->parcel_number }}</td>
                            <td>{{ number_format((float) $parcel->area_sqm, 2, ',', '.') }} m²</td>
                            <td>{{ $parcel->status->label() }}</td>
                            <td>{{ $parcel->location_description ?: '–' }}</td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('parcels.show', $parcel) }}">Öffnen</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4">Keine Parzellen gefunden.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $parcels->links() }}</div>
</div>
@endsection
