@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Rechtevorlagen</h1>
            <p class="text-secondary mb-0">Wiederverwendbare, verständliche Rechtepakete für Vorstandsmitglieder.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('permission-profiles.create') }}">Vorlage anlegen</a>
    </div>

    <div class="alert alert-info">
        Vorlagenänderungen wirken nur bei der nächsten Anwendung. Bereits zugewiesene Konten behalten ihren bisherigen Rechte-Snapshot.
    </div>

    <div class="row g-4">
        @foreach ($profiles as $profile)
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between gap-3">
                            <h2 class="h5">{{ $profile->name }}</h2>
                            <span class="badge {{ $profile->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                {{ $profile->is_active ? 'Aktiv' : 'Inaktiv' }}
                            </span>
                        </div>
                        <p class="text-secondary">{{ $profile->description ?: 'Keine Beschreibung hinterlegt.' }}</p>
                        <ul class="mb-0">
                            @forelse ($profile->permission_labels as $label)
                                <li>{{ $label }}</li>
                            @empty
                                <li>Keine zusätzlichen Rechte</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="card-footer bg-body border-0">
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('permission-profiles.edit', $profile) }}">Bearbeiten</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
