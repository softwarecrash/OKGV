@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Dokumente</h1>
            <p class="text-secondary mb-0">Verträge, Protokolle, Satzungen, Belege und Fotos zentral verwalten.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('documents.create') }}">Dokument hochladen</a>
    </div>

    <form method="GET" class="card border-0 shadow-sm mb-4">
        <div class="card-body row g-3 align-items-end">
            <div class="col-lg-4">
                <label class="form-label" for="q">Suche</label>
                <input class="form-control" id="q" name="q" value="{{ request('q') }}" placeholder="Titel, Beschreibung oder Dateiname">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="type">Dokumenttyp</label>
                <select class="form-select" id="type" name="type">
                    <option value="">Alle Typen</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="visibility">Sichtbarkeit</label>
                <select class="form-select" id="visibility" name="visibility">
                    <option value="">Alle Sichtbarkeiten</option>
                    @foreach ($visibilities as $visibility)
                        <option value="{{ $visibility->value }}" @selected(request('visibility') === $visibility->value)>{{ $visibility->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <div class="form-check mb-2">
                    <input class="form-check-input" id="archived" name="archived" type="checkbox" value="1" @checked(request()->boolean('archived'))>
                    <label class="form-check-label" for="archived">Archiv anzeigen</label>
                </div>
            </div>
            <div class="col-12">
                <button class="btn btn-outline-primary">Filtern</button>
                <a class="btn btn-outline-secondary" href="{{ route('documents.index') }}">Zurücksetzen</a>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr><th>Dokument</th><th>Typ</th><th>Zuordnung</th><th>Freigabe</th><th>Version</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($documents as $document)
                        <tr>
                            <td>
                                <strong>{{ $document->title }}</strong><br>
                                <small class="text-secondary">{{ $document->original_name }} · {{ number_format($document->file_size / 1024, 0, ',', '.') }} KiB</small>
                            </td>
                            <td>{{ $document->type->label() }}</td>
                            <td>
                                @if ($document->member)
                                    {{ $document->member->last_name }}, {{ $document->member->first_name }}<br>
                                @endif
                                @if ($document->parcel)
                                    Parzelle {{ $document->parcel->parcel_number }}
                                @endif
                                @if (! $document->member && ! $document->parcel)
                                    <span class="text-secondary">Allgemein</span>
                                @endif
                            </td>
                            <td>
                                {{ $document->visibility->label() }}<br>
                                <small class="{{ $document->isPublished() ? 'text-success' : 'text-secondary' }}">
                                    {{ $document->isPublished() ? 'veröffentlicht' : 'nicht veröffentlicht' }}
                                </small>
                            </td>
                            <td>{{ $document->current_version }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('documents.show', $document) }}">Details</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <strong>Keine passenden Dokumente vorhanden.</strong><br>
                                <span class="text-secondary">Passe die Filter an oder lade das erste Dokument hoch.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $documents->links() }}</div>

    @if ($invoices)
        <section class="mt-5">
            <h2 class="h4 mb-1">Systemrechnungen</h2>
            <p class="text-secondary">Freigegebene Rechnungen sind unveränderliche Systemdokumente und werden nicht als Upload dupliziert.</p>
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Rechnung</th><th>Empfänger</th><th>Zeitraum</th><th>Betrag</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($invoices as $invoice)
                                <tr>
                                    <td>{{ $invoice->invoice_number }}</td>
                                    <td>{{ $invoice->member->last_name }}, {{ $invoice->member->first_name }}</td>
                                    <td>{{ $invoice->billingPeriod->name }}</td>
                                    <td>{{ number_format((float) $invoice->total_amount, 2, ',', '.') }} €</td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('invoices.show', $invoice) }}">Anzeigen</a>
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('invoices.pdf', $invoice) }}">PDF</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-4 text-secondary">Noch keine freigegebenen Rechnungen vorhanden.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-3">{{ $invoices->links() }}</div>
        </section>
    @endif
</div>
@endsection
