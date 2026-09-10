@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2">{{ $inspection->title }}</h1>
    <x-validation-errors />
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <span>{{ $inspection->inspected_at->format('d.m.Y H:i') }} · Prüfer: {{ $inspection->inspectors }}</span>
        @if ($inspection->finalized_at)
            <a class="btn btn-sm btn-outline-primary" href="{{ route('garden-inspections.pdf', $inspection) }}">PDF-Protokoll</a>
        @elseif (auth()->user()->can('finalize', $inspection))
            <form method="POST" action="{{ route('garden-inspections.finalize', $inspection) }}">@csrf<button class="btn btn-sm btn-success">Begehung abschließen</button></form>
        @endif
    </div>
    @if ($inspection->instructions)
        <div class="alert alert-secondary">{!! nl2br(e($inspection->instructions)) !!}</div>
    @endif
    <h2 class="h4">Feststellungen</h2>
    @forelse ($findings as $finding)
        <article class="card card-body mb-3">
            <strong>Parzelle {{ $finding->parcel->parcel_number }} · {{ ucfirst($finding->category) }} · {{ $finding->status->label() }}</strong>
            <p class="mb-1">{!! nl2br(e($finding->description)) !!}</p>
            @if ($finding->due_at)<small>Frist: {{ $finding->due_at->format('d.m.Y') }}</small>@endif
            @if ($finding->responsibleMember)<small class="d-block">Zuständig: {{ $finding->responsibleMember->full_name }}</small>@endif
            @if ($finding->task)<small class="d-block">Verknüpfte Aufgabe: {{ $finding->task->title }}</small>@endif
            @if ($finding->photo_path)<a href="{{ route('garden-inspection-findings.photo', $finding) }}">Foto ansehen</a>@endif
            @can('create', App\Models\GardenInspection::class)
                @if ($finding->status->value === 'open')
                    <form method="POST" action="{{ route('garden-inspection-findings.resolve', $finding) }}" class="mt-2">@csrf<button class="btn btn-sm btn-outline-success">Nachkontrolle: erledigt</button></form>
                @endif
            @endcan
        </article>
    @empty
        <div class="alert alert-info">Noch keine Feststellung erfasst.</div>
    @endforelse
    @can('update', $inspection)
        <form method="POST" enctype="multipart/form-data" action="{{ route('garden-inspections.findings.store', $inspection) }}" class="card card-body">
            @csrf
            <h2 class="h5">Feststellung erfassen</h2>
            <select class="form-select mb-2" name="parcel_id" required>
                <option value="">Parzelle wählen</option>
                @foreach ($parcels as $parcel)<option value="{{ $parcel->id }}">{{ $parcel->parcel_number }}</option>@endforeach
            </select>
            <select class="form-select mb-2" name="category" required>
                @foreach (['general' => 'Allgemein', 'cabin' => 'Laube', 'planting' => 'Bepflanzung', 'paths' => 'Wege', 'utilities' => 'Wasser/Strom', 'safety' => 'Sicherheit', 'other' => 'Sonstiges'] as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
            </select>
            <textarea class="form-control mb-2" name="description" placeholder="Verständliche Beschreibung für Pächter" required></textarea>
            <input class="form-control mb-2" type="date" name="due_at">
            <select class="form-select mb-2" name="responsible_member_id">
                <option value="">Keine zuständige Person festlegen</option>
                @foreach ($members as $member)<option value="{{ $member->id }}">{{ $member->full_name }}</option>@endforeach
            </select>
            <select class="form-select mb-2" name="task_id">
                <option value="">Keine vorhandene Aufgabe verknüpfen</option>
                @foreach ($tasks as $task)<option value="{{ $task->id }}">{{ $task->title }}</option>@endforeach
            </select>
            <input class="form-control mb-2" type="file" name="photo" accept="image/jpeg,image/png,image/webp">
            <textarea class="form-control mb-2" name="internal_note" placeholder="Interne Notiz (nicht für Pächter sichtbar)"></textarea>
            <button class="btn btn-primary align-self-start">Feststellung speichern</button>
        </form>
    @endcan
</div>
@endsection
