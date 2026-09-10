@extends('layouts.app')
@section('content')
<div class="container"><h1 class="h2">Mitgliederversammlungen</h1><p class="text-secondary">Einladungen, Teilnahme und protokollierte Beschlüsse. Satzung und rechtliche Voraussetzungen sind vor jeder Einberufung durch den Verein zu prüfen.</p>@can('create', App\Models\MemberAssembly::class)<a class="btn btn-primary mb-3" href="{{ route('member-assemblies.create') }}">Versammlung anlegen</a>@endcan
@forelse($assemblies as $assembly)<article class="card card-body mb-3"><h2 class="h5"><a href="{{ route('member-assemblies.show',$assembly) }}">{{ $assembly->title }}</a></h2><span>{{ $assembly->mode->label() }} · {{ $assembly->scheduled_at->format('d.m.Y H:i') }} · {{ $assembly->statusLabel() }}</span></article>@empty<div class="alert alert-info">Derzeit gibt es keine einberufene Mitgliederversammlung.</div>@endforelse{{ $assemblies->links() }}</div>
@endsection
