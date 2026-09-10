@extends('layouts.app')
@section('content')
<div class="container"><h1 class="h2">Gartenbegehungen</h1><p class="text-secondary">Prüfungen und Fristen zu Parzellen. Pächter sehen nur Hinweise zu eigenen Parzellen.</p>@can('create', App\Models\GardenInspection::class)<a href="{{ route('garden-inspections.create') }}" class="btn btn-primary mb-3">Begehung anlegen</a>@endcan@forelse($rows as $row)<article class="card card-body mb-3"><a class="h5" href="{{ route('garden-inspections.show',$row) }}">{{ $row->title }}</a><span>{{ $row->inspected_at->format('d.m.Y H:i') }} @if($row->finalized_at) · Abgeschlossen @endif</span></article>@empty<div class="alert alert-info">Noch keine Gartenbegehung erfasst.</div>@endforelse{{ $rows->links() }}</div>
@endsection
