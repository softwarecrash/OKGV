@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h2 mb-1">Warteliste</h1>
            <p class="text-secondary mb-0">Interessenten nach Priorität und Eingangsdatum bearbeiten.</p>
        </div>
        @can('create', App\Models\WaitingListEntry::class)
            <a class="btn btn-primary" href="{{ route('waiting-list-entries.create') }}">Eintrag anlegen</a>
        @endcan
    </div>

    <form class="card card-body border-0 shadow-sm mb-4" method="GET">
        <div class="row g-2">
            <div class="col-lg-5">
                <label class="form-label" for="q">Suche</label>
                <input class="form-control" id="q" name="q" value="{{ request('q') }}" placeholder="Name, E-Mail oder Telefonnummer">
            </div>
            <div class="col-lg-3">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Nur offene Vorgänge</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label" for="priority">Priorität</label>
                <select class="form-select" id="priority" name="priority">
                    <option value="">Alle</option>
                    @foreach (range(1, 5) as $priority)
                        <option value="{{ $priority }}" @selected((string) request('priority') === (string) $priority)>{{ $priority }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 d-flex align-items-end">
                <button class="btn btn-outline-primary w-100" type="submit">Filtern</button>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Priorität</th>
                        <th>Eingang</th>
                        <th>Name</th>
                        <th>Kontakt</th>
                        <th>Status</th>
                        <th class="text-end">Aktion</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td><span class="badge text-bg-secondary">{{ $entry->priority }}</span></td>
                            <td>{{ $entry->applied_at->format('d.m.Y') }}</td>
                            <td>{{ $entry->full_name }}</td>
                            <td>
                                <a href="mailto:{{ $entry->email }}">{{ $entry->email }}</a>
                                @if ($entry->mobile || $entry->phone)
                                    <div class="small text-secondary">{{ $entry->mobile ?: $entry->phone }}</div>
                                @endif
                            </td>
                            <td>{{ $entry->status->label() }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('waiting-list-entries.show', $entry) }}">Öffnen</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <strong>Keine passenden Wartelisteneinträge gefunden.</strong><br>
                                <span class="text-secondary">Prüfe Suche und Filter oder lege einen neuen Eintrag an.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $entries->links() }}</div>
</div>
@endsection
