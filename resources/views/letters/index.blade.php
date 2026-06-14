@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div><h1 class="h2 mb-1">Briefe</h1><p class="text-secondary mb-0">Gespeicherte Anschriften und unveränderte PDF-Ausgaben.</p></div>
        <a class="btn btn-primary" href="{{ route('letters.create') }}">Brief erstellen</a>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Betreff</th><th>Empfänger</th><th>Erstellt</th><th></th></tr></thead>
                <tbody>
                    @forelse ($letters as $letter)
                        <tr>
                            <td>{{ $letter->subject }}</td>
                            <td>{{ $letter->recipient_name }}<br><small class="text-secondary">{{ $letter->zip }} {{ $letter->city }}</small></td>
                            <td>{{ $letter->created_at->format('d.m.Y H:i') }}</td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('letters.show', $letter) }}">Öffnen</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-4"><strong>Noch kein Brief vorhanden.</strong><br><span class="text-secondary">Erstelle einen Einzelbrief an ein Mitglied oder eine freie Anschrift.</span></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $letters->links() }}</div>
</div>
@endsection
