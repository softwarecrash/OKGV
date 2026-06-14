@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-2">Zählerstandsmeldungen</h1>
    <p class="text-secondary mb-4">
        @if (auth()->user()->role === App\Enums\UserRole::Tenant)
            Hier siehst du den Prüfstatus deiner Meldungen. Abgesendete Werte können nicht nachträglich verändert werden.
        @else
            Prüfe Foto, Datum und Plausibilität. Erst eine Bestätigung übernimmt den Wert in die Zählerhistorie.
        @endif
    </p>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Parzelle / Zähler</th><th>Pächter</th><th>Datum</th><th>Stand</th><th>Foto</th><th>Status</th><th>Prüfung</th></tr></thead>
                <tbody>
                    @forelse ($submissions as $submission)
                        <tr>
                            <td>{{ $submission->meter->parcel->parcel_number }} · {{ $submission->meter->meter_number }}</td>
                            <td>{{ $submission->submitter->member?->full_name ?? $submission->submitter->name }}</td>
                            <td>{{ $submission->reading_date->format('d.m.Y') }}</td>
                            <td>{{ $submission->reading_value }}</td>
                            <td>
                                @if ($submission->photo_path)
                                    <a href="{{ route('meter-reading-submissions.photo', $submission) }}">Privates Foto</a>
                                @else
                                    –
                                @endif
                            </td>
                            <td>{{ $submission->status->label() }}</td>
                            <td style="min-width: 18rem">
                                @can('review', $submission)
                                    <form class="d-flex gap-2 mb-2" method="POST" action="{{ route('meter-reading-submissions.approve', $submission) }}">
                                        @csrf
                                        <input class="form-control form-control-sm" name="review_note" maxlength="255" placeholder="Prüfhinweis (optional)">
                                        <button class="btn btn-sm btn-success" onclick="return confirm('Stand bestätigen und in die Historie übernehmen?')">Bestätigen</button>
                                    </form>
                                    <form class="d-flex gap-2" method="POST" action="{{ route('meter-reading-submissions.reject', $submission) }}">
                                        @csrf
                                        <input class="form-control form-control-sm" name="review_note" maxlength="255" required placeholder="Ablehnungsgrund">
                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Meldung wirklich ablehnen?')">Ablehnen</button>
                                    </form>
                                @else
                                    {{ $submission->review_note ?? '–' }}
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4"><strong>Keine Zählerstandsmeldungen vorhanden.</strong></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $submissions->links() }}</div>
</div>
@endsection
