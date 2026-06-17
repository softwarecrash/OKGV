@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-2">Registrierungsanfragen</h1>
    <p class="text-secondary mb-4">Prüfe Identität und Pachtzuordnung sorgfältig. Das Benutzerkonto existiert bereits, wird aber erst nach Freigabe nutzbar.</p>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Name</th><th>E-Mail</th><th>Parzelle</th><th>Konto</th><th>Eingang</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($registrationRequests as $entry)
                        <tr>
                            <td>{{ $entry->full_name }}</td>
                            <td>{{ $entry->email }}</td>
                            <td>{{ $entry->parcel_number ?? 'Keine angegeben' }}</td>
                            <td>
                                @php($resolvedUser = $entry->resolvedUser())
                                @if ($resolvedUser)
                                    <span class="badge text-bg-success">angelegt</span>
                                    @if (! $entry->user_id)
                                        <span class="badge text-bg-info">per E-Mail gefunden</span>
                                    @endif
                                    @if ($resolvedUser->hasVerifiedEmail())
                                        <span class="badge text-bg-success">E-Mail bestätigt</span>
                                    @else
                                        <span class="badge text-bg-warning">E-Mail offen</span>
                                    @endif
                                @else
                                    <span class="badge text-bg-secondary">ältere Anfrage</span>
                                @endif
                            </td>
                            <td>{{ $entry->created_at->format('d.m.Y H:i') }}</td>
                            <td>{{ $entry->status->label() }}</td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('registration-requests.show', $entry) }}">Prüfen</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4"><strong>Keine Registrierungsanfragen vorhanden.</strong></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $registrationRequests->links() }}</div>
</div>
@endsection
