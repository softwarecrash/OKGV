@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">{{ $document->title }}</h1>
            <p class="text-secondary mb-0">{{ $document->type->label() }} · Version {{ $document->current_version }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary" href="{{ route('documents.download', $document) }}">Aktuelle Datei herunterladen</a>
            @can('update', $document)
                <a class="btn btn-outline-primary" href="{{ route('documents.edit', $document) }}">Bearbeiten</a>
            @endcan
        </div>
    </div>

    @if ($document->archived_at)
        <div class="alert alert-warning">Dieses Dokument ist seit {{ $document->archived_at->format('d.m.Y H:i') }} archiviert. Alle Freigaben sind beendet.</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Dokumentdaten</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Beschreibung</dt>
                        <dd class="col-sm-8">{{ $document->description ?: 'Keine Beschreibung' }}</dd>
                        <dt class="col-sm-4">Sichtbarkeit</dt>
                        <dd class="col-sm-8">{{ $document->visibility->label() }}</dd>
                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">{{ $document->isPublished() ? 'Veröffentlicht' : 'Nicht veröffentlicht' }}</dd>
                        <dt class="col-sm-4">Mitglied</dt>
                        <dd class="col-sm-8">
                            {{ $document->member ? $document->member->last_name.', '.$document->member->first_name : 'Nicht zugeordnet' }}
                        </dd>
                        <dt class="col-sm-4">Parzelle</dt>
                        <dd class="col-sm-8">{{ $document->parcel ? 'Parzelle '.$document->parcel->parcel_number : 'Nicht zugeordnet' }}</dd>
                        <dt class="col-sm-4">Hochgeladen von</dt>
                        <dd class="col-sm-8">{{ $document->uploader->name }} am {{ $document->created_at->format('d.m.Y H:i') }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Freigabe</h2>
                    @if ($document->visibility === App\Enums\DocumentVisibility::Public && $document->isPublished())
                        <label class="form-label" for="public-link">Öffentlicher Freigabelink</label>
                        <input class="form-control" id="public-link" readonly value="{{ route('documents.public', $document->public_token) }}">
                        <div class="form-text">Jede Person mit diesem nicht erratbaren Link kann die aktuelle Datei herunterladen. Eine Rücknahme der Veröffentlichung beendet den Zugriff sofort.</div>
                    @elseif ($document->visibility === App\Enums\DocumentVisibility::Tenant && $document->isPublished())
                        <p class="mb-0">Das zugeordnete Mitglied beziehungsweise aktuelle Pächter der Parzelle sehen die Datei im Pächterportal.</p>
                    @else
                        <p class="mb-0 text-secondary">Für dieses Dokument ist derzeit kein externer Zugriff aktiv.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-body"><h2 class="h5 mb-0">Dateiversionen</h2></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Version</th><th>Datei</th><th>Hochgeladen</th><th></th></tr></thead>
                <tbody>
                    @foreach ($document->versions as $version)
                        <tr>
                            <td>{{ $version->version_number }}</td>
                            <td>{{ $version->original_name }}<br><small class="text-secondary">{{ number_format($version->file_size / 1024, 0, ',', '.') }} KiB</small></td>
                            <td>{{ $version->uploader->name }}<br><small class="text-secondary">{{ $version->created_at->format('d.m.Y H:i') }}</small></td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="{{ route('documents.versions.download', [$document, $version]) }}">Herunterladen</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @can('archive', $document)
        <div class="card border-danger mt-4">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <strong>Dokument archivieren</strong>
                    <div class="text-secondary">Das Dokument bleibt mit allen Versionen erhalten, wird aber nicht mehr freigegeben.</div>
                </div>
                <form method="POST" action="{{ route('documents.archive', $document) }}" onsubmit="return confirm('Dokument wirklich archivieren und alle Freigaben beenden?')">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-outline-danger">Archivieren</button>
                </form>
            </div>
        </div>
    @endcan
</div>
@endsection
