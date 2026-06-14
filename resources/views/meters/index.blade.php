@extends('layouts.app')
@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">Zähler</h1>
        @can('create', App\Models\Meter::class)
            <a class="btn btn-primary" href="{{ route('meters.create') }}">Zähler anlegen</a>
        @endcan
    </div>
    <form class="card card-body border-0 shadow-sm mb-4" method="GET">
        <div class="row g-2">
            <div class="col-md-6"><label class="form-label" for="q">Suche</label><input class="form-control" id="q" name="q" value="{{ request('q') }}" placeholder="Zähler- oder Parzellennummer"></div>
            <div class="col-md-2"><label class="form-label" for="type">Typ</label><select class="form-select" id="type" name="type"><option value="">Alle</option>@foreach($types as $type)<option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">Alle</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-primary w-100">Filtern</button></div>
        </div>
    </form>
    <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead><tr><th>Zähler</th><th>Parzelle</th><th>Typ</th><th>Status</th><th>Einbau</th><th></th></tr></thead>
        <tbody>@forelse($meters as $meter)<tr><td>{{ $meter->meter_number }}</td><td>{{ $meter->parcel->parcel_number }}</td><td>{{ $meter->type->label() }}</td><td>{{ $meter->status->label() }}</td><td>{{ $meter->installed_at->format('d.m.Y') }}</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('meters.show', $meter) }}">Öffnen</a></td></tr>@empty<tr><td colspan="6" class="text-center py-4"><strong>Keine passenden Zähler gefunden.</strong><br><span class="text-secondary">Prüfe die Filter oder lege den ersten Zähler mit Startstand an.</span></td></tr>@endforelse</tbody>
    </table></div></div>
    <div class="mt-3">{{ $meters->links() }}</div>
</div>
@endsection
