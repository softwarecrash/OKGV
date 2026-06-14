@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Arbeitsstundenmeldungen</h1>
            <p class="text-secondary mb-0">
                @if (auth()->user()->role === App\Enums\UserRole::Tenant)
                    Deine Meldungen können nach dem Absenden nicht verändert werden.
                @else
                    Prüfe Tätigkeit, Parzellenzuordnung und Nachweis. Erst die Bestätigung übernimmt die Stunden.
                @endif
            </p>
        </div>
        @if (auth()->user()->role === App\Enums\UserRole::Tenant && auth()->user()->member)
            <a class="btn btn-primary" href="{{ route('work-hour-submissions.create') }}">Arbeitsstunden melden</a>
        @endif
    </div>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Parzelle</th><th>Pächter</th><th>Datum</th><th>Stunden</th><th>Tätigkeit / Foto</th><th>Status</th><th>Prüfung</th></tr></thead>
                <tbody>
                    @forelse ($submissions as $submission)
                        <tr>
                            <td>{{ $submission->parcel->parcel_number }}</td>
                            <td>{{ $submission->submitter->member?->full_name ?? $submission->submitter->name }}</td>
                            <td>{{ $submission->worked_at->format('d.m.Y') }}</td>
                            <td>{{ number_format((float) $submission->hours, 2, ',', '.') }} Std.</td>
                            <td>
                                {{ $submission->description }}
                                @if ($submission->photo_path)
                                    · <a href="{{ route('work-hour-submissions.photo', $submission) }}">Privates Foto</a>
                                @endif
                            </td>
                            <td>{{ $submission->status->label() }}</td>
                            <td style="min-width:18rem">
                                @can('review', $submission)
                                    <form class="d-flex gap-2 mb-2" method="POST" action="{{ route('work-hour-submissions.approve', $submission) }}">
                                        @csrf
                                        <input class="form-control form-control-sm" name="review_note" maxlength="255" placeholder="Prüfhinweis (optional)">
                                        <button class="btn btn-sm btn-success" onclick="return confirm('Stunden bestätigen und dem Parzellenkonto gutschreiben?')">Bestätigen</button>
                                    </form>
                                    <form class="d-flex gap-2" method="POST" action="{{ route('work-hour-submissions.reject', $submission) }}">
                                        @csrf
                                        <input class="form-control form-control-sm" name="review_note" maxlength="255" required placeholder="Ablehnungsgrund">
                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Meldung ablehnen?')">Ablehnen</button>
                                    </form>
                                @else
                                    {{ $submission->review_note ?? '–' }}
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4"><strong>Keine Arbeitsstundenmeldungen vorhanden.</strong></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $submissions->links() }}</div>
</div>
@endsection
