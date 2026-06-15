@extends('layouts.app')

@section('content')
<div class="container">
    <div class="mb-4">
        <h1 class="h2 mb-1">Datenübertragung</h1>
        <p class="text-secondary mb-0">
            Importiere Stammdaten aus geprüften CSV-Dateien oder exportiere vorhandene Daten für die weitere Bearbeitung.
        </p>
    </div>

    <x-validation-errors />

    <div class="alert alert-info">
        <strong>Sicherer CSV-Import:</strong>
        Verwende die angebotenen Vorlagen unverändert und speichere Dateien als UTF-8.
        Mitglieder und Parzellen mit vorhandener Nummer werden aktualisiert.
        Zähler und Zählerstände sind historische Datensätze und werden ausschließlich neu angelegt.
        Bei einem Fehler wird der gesamte Import zurückgerollt.
    </div>

    <div class="row g-4">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h4">CSV importieren</h2>
                    <form method="POST" action="{{ route('data-transfer.import') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="import-type">Datenart</label>
                            <select class="form-select" id="import-type" name="type" required>
                                <option value="">Bitte auswählen</option>
                                @foreach ($types as $type)
                                    @if ($type->importable())
                                        <option value="{{ $type->value }}" @selected(old('type') === $type->value)>
                                            {{ $type->label() }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="import-file">CSV-Datei</label>
                            <input class="form-control" id="import-file" name="file" type="file" accept=".csv,.txt,text/csv" required>
                            <div class="form-text">Maximal 20 MiB. Die Kopfzeile muss exakt der gewählten Vorlage entsprechen.</div>
                        </div>
                        <button class="btn btn-primary"
                                onclick="return confirm('CSV jetzt vollständig importieren? Bei fehlerfreien Zeilen werden bestehende Mitglieder oder Parzellen mit gleicher Nummer aktualisiert.')">
                            CSV prüfen und importieren
                        </button>
                    </form>

                    <hr>
                    <h3 class="h6">Importvorlagen</h3>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($types as $type)
                            @if ($type->importable())
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('data-transfer.template', $type) }}">
                                    {{ $type->label() }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h4">CSV exportieren</h2>
                    <p class="text-secondary">
                        Exporte enthalten die aktuellen Fachwerte. Rechnungen werden mit Empfängern und einzelnen Positionen ausgegeben.
                    </p>
                    <div class="d-grid gap-2">
                        @foreach ($types as $type)
                            <a class="btn btn-outline-primary d-flex justify-content-between align-items-center"
                               href="{{ route('data-transfer.export', $type) }}">
                                <span>{{ $type->label() }}</span>
                                <span>CSV herunterladen</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (auth()->user()->isAdministrator())
        <section class="mt-5">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h2 class="h3 mb-1">Backup und Wiederherstellung</h2>
                    <p class="text-secondary mb-0">
                        Vollständige Sicherungen enthalten die Datenbank und private Dokumente beziehungsweise Nachweisfotos.
                    </p>
                </div>
                <form method="POST" action="{{ route('backups.create') }}">
                    @csrf
                    <button class="btn btn-primary">Neues Backup erstellen</button>
                </form>
            </div>

            <div class="alert alert-warning">
                <strong>Vertrauliche Datei:</strong>
                Ein Backup enthält personenbezogene Daten, Passwort-Hashes, verschlüsselte Bankdaten und private Dateien.
                Bewahre Downloads geschützt auf. Die `.env` und ihre Schlüssel sind aus Sicherheitsgründen nicht enthalten und müssen separat gesichert werden.
            </div>

            <div class="card mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Datei</th>
                                    <th>Erstellt</th>
                                    <th>Größe</th>
                                    <th class="text-end">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($backups as $backup)
                                    <tr>
                                        <td><code>{{ $backup['name'] }}</code></td>
                                        <td>{{ date('d.m.Y H:i', $backup['modified_at']) }}</td>
                                        <td>{{ number_format($backup['size'] / 1024 / 1024, 2, ',', '.') }} MiB</td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-2">
                                                <a class="btn btn-sm btn-outline-primary" href="{{ route('backups.download', $backup['name']) }}">Herunterladen</a>
                                                <form method="POST" action="{{ route('backups.destroy', $backup['name']) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('Dieses Backup dauerhaft vom Server löschen?')">
                                                        Löschen
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <strong>Noch kein Backup vorhanden.</strong><br>
                                            <span class="text-secondary">Erstelle vor größeren Änderungen die erste Sicherung.</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card border-danger">
                <div class="card-body">
                    <h3 class="h4 text-danger">Backup wiederherstellen</h3>
                    <p>
                        Die aktuelle Datenbank und die gesicherten privaten Dateien werden ersetzt.
                        Vorher legt OKGV automatisch ein Sicherheitsbackup des jetzigen Zustands an.
                        Zulässig sind ausschließlich unveränderte OKGV-Backups der aktuell laufenden Version.
                    </p>
                    <form method="POST" action="{{ route('backups.restore') }}" enctype="multipart/form-data"
                          onsubmit="return confirm('Wiederherstellung wirklich starten? Währenddessen darf niemand in OKGV arbeiten.')">
                        @csrf
                        <div class="row g-3">
                            <div class="col-lg-6">
                                <label class="form-label" for="backup-file">OKGV-Backup</label>
                                <input class="form-control" id="backup-file" name="backup" type="file" accept=".zip,application/zip" required>
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label" for="backup-password">Eigenes Passwort</label>
                                <input class="form-control" id="backup-password" name="password" type="password" autocomplete="current-password" required>
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label" for="backup-confirmation">Bestätigung</label>
                                <input class="form-control" id="backup-confirmation" name="confirmation" type="text" placeholder="WIEDERHERSTELLEN" required>
                            </div>
                        </div>
                        <button class="btn btn-danger mt-3">Backup wiederherstellen</button>
                    </form>
                </div>
            </div>
        </section>
    @endif
</div>
@endsection
