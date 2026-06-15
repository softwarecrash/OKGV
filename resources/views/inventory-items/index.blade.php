@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h2 mb-1">Inventar</h1>
            <p class="text-secondary mb-0">Vereinsgegenstände, Verfügbarkeit und Ausgaben verwalten.</p>
        </div>
        @can('create', App\Models\InventoryItem::class)
            <a class="btn btn-primary" href="{{ route('inventory-items.create') }}">Gegenstand anlegen</a>
        @endcan
    </div>

    @if ($overdueCount > 0)
        <div class="alert alert-warning">
            {{ $overdueCount }} {{ $overdueCount === 1 ? 'Ausgabe ist' : 'Ausgaben sind' }} überfällig.
            Öffne den betreffenden Gegenstand, um die Rückgabe zu erfassen.
        </div>
    @endif

    <form class="card card-body border-0 shadow-sm mb-4" method="GET">
        <div class="row g-2">
            <div class="col-lg-5">
                <label class="form-label" for="q">Suche</label>
                <input class="form-control" id="q" name="q" value="{{ request('q') }}" placeholder="Inventarnummer, Name, Seriennummer oder Standort">
            </div>
            <div class="col-lg-3">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Alle Status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label" for="category">Kategorie</label>
                <select class="form-select" id="category" name="category">
                    <option value="">Alle Kategorien</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
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
                        <th>Inventarnummer</th>
                        <th>Gegenstand</th>
                        <th>Kategorie</th>
                        <th>Standort</th>
                        <th>Status</th>
                        <th class="text-end">Aktion</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        @php($openLoan = $item->openLoans->first())
                        <tr>
                            <td>{{ $item->inventory_number }}</td>
                            <td>
                                {{ $item->name }}
                                @if ($openLoan?->is_overdue)
                                    <div class="small text-danger">Rückgabe seit {{ $openLoan->due_at->format('d.m.Y') }} überfällig</div>
                                @endif
                            </td>
                            <td>{{ $item->category ?: '–' }}</td>
                            <td>{{ $item->location ?: '–' }}</td>
                            <td>{{ $item->status->label() }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('inventory-items.show', $item) }}">Öffnen</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <strong>Keine passenden Inventargegenstände gefunden.</strong><br>
                                <span class="text-secondary">Prüfe Suche und Filter oder lege den ersten Gegenstand an.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $items->links() }}</div>
</div>
@endsection
