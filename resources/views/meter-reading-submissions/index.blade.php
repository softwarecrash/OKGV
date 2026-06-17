@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-2">Zählerstandsmeldungen</h1>
    <p class="text-secondary mb-4">
        @if (auth()->user()->hasTenantAccess())
            Hier siehst du den Prüfstatus deiner Meldungen. Abgesendete Werte können nicht nachträglich verändert werden.
        @else
            Prüfe Foto, Datum und Plausibilität. Erst eine Bestätigung übernimmt den Wert in die Zählerhistorie. Bearbeitete Meldungen bleiben als nachvollziehbare Historie sichtbar.
        @endif
    </p>
    <x-validation-errors />
    @if (auth()->user()->hasTenantAccess() && $actionIndicators['meter_readings'] > 0)
        <div class="alert alert-warning" role="status">
            <strong>
                {{ $actionIndicators['meter_readings'] }}
                {{ $actionIndicators['meter_readings'] === 1 ? 'Meldung wurde' : 'Meldungen wurden' }}
                abgelehnt und muss erneut eingereicht werden.
            </strong>
            <div>Die betroffene Meldung ist unten hervorgehoben. Beachte den Ablehnungsgrund und melde anschließend einen korrigierten Stand.</div>
        </div>
    @endif
    @if (session('review_error'))
        <div class="alert alert-danger" role="alert" aria-live="polite">
            <strong>Zählerstand konnte nicht bestätigt werden.</strong>
            <div>{{ session('review_error') }}</div>
            <div class="small mt-1">Prüfe den gemeldeten Wert und die bisherige Zählerhistorie. Ist die Meldung falsch, lehne sie mit einer verständlichen Begründung ab.</div>
        </div>
    @endif
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Parzelle / Zähler</th><th>Pächter</th><th>Datum</th><th>Vorheriger Stand</th><th>Gemeldeter Stand</th><th>Foto</th><th>Status</th><th>Prüfung</th></tr></thead>
                <tbody>
                    @forelse ($submissions as $submission)
                        <tr @class([
                            'table-danger' => (int) session('review_submission_id') === $submission->id,
                            'table-warning' => $submission->requires_tenant_action,
                        ])>
                            <td>{{ $submission->meter->parcel->parcel_number }} · {{ $submission->meter->meter_number }}</td>
                            <td>{{ $submission->submitter->member?->full_name ?? $submission->submitter->name }}</td>
                            <td>{{ $submission->reading_date->format('d.m.Y') }}</td>
                            <td>
                                <strong>{{ $submission->previous_reading_value }} {{ $submission->meter->type->unit() }}</strong>
                                <div class="small text-secondary">
                                    @if ($submission->previous_reading_is_installation)
                                        Einbaustand vom {{ $submission->previous_reading_date->format('d.m.Y') }}
                                    @else
                                        Abgelesen am {{ $submission->previous_reading_date->format('d.m.Y') }}
                                    @endif
                                </div>
                            </td>
                            <td @class(['text-danger' => $submission->is_below_previous_reading])>
                                <strong>{{ $submission->reading_value }} {{ $submission->meter->type->unit() }}</strong>
                                @if ($submission->is_below_previous_reading)
                                    <div class="small fw-semibold">Niedriger als der vorherige Stand</div>
                                @endif
                            </td>
                            <td>
                                @if ($submission->photo_path)
                                    <button
                                        class="btn btn-sm btn-outline-primary"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#meter-reading-photo-modal"
                                        data-private-photo-url="{{ route('meter-reading-submissions.photo', $submission) }}"
                                        data-private-photo-name="{{ $submission->photo_original_name ?? 'Zählerstandsfoto' }}">
                                        Foto ansehen
                                    </button>
                                @else
                                    –
                                @endif
                            </td>
                            <td>{{ $submission->status->label() }}</td>
                            <td style="min-width: 18rem">
                                @can('review', $submission)
                                    <form class="d-flex gap-2 mb-2" method="POST" action="{{ route('meter-reading-submissions.approve', $submission) }}">
                                        @csrf
                                        <input type="hidden" name="submission_id" value="{{ $submission->id }}">
                                        <input class="form-control form-control-sm" name="review_note" maxlength="255" placeholder="Prüfhinweis (optional)">
                                        <button class="btn btn-sm btn-success" onclick="return confirm('Stand bestätigen und in die Historie übernehmen?')">Bestätigen</button>
                                    </form>
                                    <form class="d-flex gap-2" method="POST" action="{{ route('meter-reading-submissions.reject', $submission) }}">
                                        @csrf
                                        <input type="hidden" name="submission_id" value="{{ $submission->id }}">
                                        <input class="form-control form-control-sm" name="review_note" maxlength="255" required placeholder="Ablehnungsgrund">
                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Meldung wirklich ablehnen?')">Ablehnen</button>
                                    </form>
                                @else
                                    @if ($submission->requires_tenant_action)
                                        <div class="d-flex align-items-center gap-1 fw-semibold text-warning-emphasis">
                                            <x-action-indicator :count="1" label="erneute Meldung erforderlich" />
                                            Erneute Meldung erforderlich
                                        </div>
                                        <div class="small mt-1">{{ $submission->review_note ?: 'Bitte reiche einen korrigierten Zählerstand ein.' }}</div>
                                        <a class="btn btn-sm btn-warning mt-2" href="{{ route('meter-reading-submissions.create', $submission->meter) }}">
                                            Korrigierten Stand melden
                                        </a>
                                    @else
                                        {{ $submission->review_note ?? '–' }}
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-4"><strong>Keine Zählerstandsmeldungen vorhanden.</strong></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $submissions->links() }}</div>
</div>

<div
    class="modal fade"
    id="meter-reading-photo-modal"
    tabindex="-1"
    aria-labelledby="meter-reading-photo-modal-title"
    aria-hidden="true"
    data-private-photo-modal>
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title fs-5" id="meter-reading-photo-modal-title">Zählerstandsfoto</h2>
                    <div class="small text-secondary" data-private-photo-name></div>
                </div>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <div class="btn-group" role="group" aria-label="Foto vergrößern oder verkleinern">
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-private-photo-zoom-out title="Verkleinern">−</button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-private-photo-reset>Einpassen</button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-private-photo-zoom-in title="Vergrößern">+</button>
                    </div>
                    <span class="small text-secondary" data-private-photo-zoom-label aria-live="polite">100 %</span>
                </div>
                <div class="private-photo-viewport" data-private-photo-viewport>
                    <img class="private-photo-image" alt="Privates Zählerstandsfoto" data-private-photo-image>
                </div>
                <p class="small text-secondary mt-2 mb-0">Vergrößere das Foto mit den Schaltflächen oder Strg und Mausrad. Ein vergrößertes Foto kannst du mit gedrückter Maustaste verschieben.</p>
            </div>
        </div>
    </div>
</div>
@endsection
