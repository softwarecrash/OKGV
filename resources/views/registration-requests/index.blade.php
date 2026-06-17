@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-2">Registrierungsanfragen</h1>
    <p class="text-secondary mb-4">Prüfe Identität und Pachtzuordnung sorgfältig. Erst die Freigabe erzeugt ein aktives Benutzerkonto.</p>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Name</th><th>E-Mail</th><th>Parzelle</th><th>Eingang</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($registrationRequests as $entry)
                        <tr>
                            <td>{{ $entry->full_name }}</td>
                            <td>{{ $entry->email }}</td>
                            <td>{{ $entry->parcel_number ?? 'Keine angegeben' }}</td>
                            <td>{{ $entry->created_at->format('d.m.Y H:i') }}</td>
                            <td>{{ $entry->status->label() }}</td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('registration-requests.show', $entry) }}">Prüfen</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4"><strong>Keine Registrierungsanfragen vorhanden.</strong></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $registrationRequests->links() }}</div>
</div>
@endsection
