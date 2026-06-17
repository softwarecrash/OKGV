@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-4">Registrierungsanfrage prüfen</h1>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Angegebener Name</dt><dd class="col-sm-9">{{ $registrationRequest->full_name }}</dd>
                <dt class="col-sm-3">E-Mail</dt><dd class="col-sm-9">{{ $registrationRequest->email }}</dd>
                <dt class="col-sm-3">Parzelle</dt><dd class="col-sm-9">{{ $registrationRequest->parcel_number ?? 'Keine angegeben' }}</dd>
                <dt class="col-sm-3">Status</dt><dd class="col-sm-9">{{ $registrationRequest->status->label() }}</dd>
                @if ($registrationRequest->reviewed_at)
                    <dt class="col-sm-3">Bearbeitet</dt><dd class="col-sm-9">{{ $registrationRequest->reviewed_at->format('d.m.Y H:i') }}{{ $registrationRequest->review_note ? ' · '.$registrationRequest->review_note : '' }}</dd>
                @endif
            </dl>
        </div>
    </div>

    @can('review', $registrationRequest)
        <div class="alert alert-warning">
            Vergleiche die Angaben mit dem Pachtvertrag oder einem anderen verlässlichen Vereinsnachweis.
            Wenn keine Parzellennummer angegeben wurde, kann das Konto ohne Mitgliedsverknüpfung freigegeben und später zugeordnet oder hochgestuft werden.
        </div>
        @if ($recommendedCandidate)
            <div class="alert alert-success">
                <strong>Empfohlene Zuordnung:</strong>
                {{ $recommendedCandidate->member_number }} · {{ $recommendedCandidate->full_name }}
                @if ($recommendedCandidate->registration_email_matches)
                    <span class="badge text-bg-success ms-1">E-Mail stimmt überein</span>
                @endif
                @if ($recommendedCandidate->registration_first_name_matches && $recommendedCandidate->registration_last_name_matches)
                    <span class="badge text-bg-success ms-1">Name stimmt überein</span>
                @endif
                <div class="small mt-1">Die Empfehlung ist nur eine Prüfhilfe. Die verbindliche Zuordnung bleibt beim Vorstand.</div>
            </div>
        @endif
        <div class="row g-4">
            <div class="col-lg-7">
                <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('registration-requests.approve', $registrationRequest) }}">
                    @csrf
                    <x-validation-errors />
                    <h2 class="h5">Konto freigeben</h2>
                    <label class="form-label" for="member_id">Mitglied zuordnen</label>
                    <select
                        class="form-select"
                        id="member_id"
                        name="member_id"
                        data-registration-member-select
                        data-registration-email="{{ $registrationRequest->email }}">
                        <option value="">Kein Mitglied zuordnen</option>
                        @foreach ($candidates as $member)
                            <option
                                value="{{ $member->id }}"
                                data-member-number="{{ $member->member_number }}"
                                data-member-name="{{ $member->full_name }}"
                                data-member-email="{{ $member->email }}"
                                @selected((string) old('member_id', $recommendedCandidate?->id) === (string) $member->id)>
                                {{ $member->member_number }} · {{ $member->full_name }}
                                @if ($member->registration_recommended)
                                    · Empfohlen
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text mb-3">
                        @if ($registrationRequest->parcel_id)
                            Angezeigt werden nur aktuelle Pächter dieser Parzelle ohne vorhandenes Benutzerkonto. Für Pächterregistrierungen ist eine Zuordnung erforderlich.
                        @else
                            Ohne Parzellennummer ist die Zuordnung optional. Das Konto kann später mit einem Mitglied oder einer Parzelle verbunden werden.
                        @endif
                    </div>
                    <div class="border rounded p-3 mb-3 d-none" data-registration-member-preview>
                        <h3 class="h6">Vergleich vor der Freigabe</h3>
                        <dl class="row small mb-0">
                            <dt class="col-sm-5">Ausgewähltes Mitglied</dt>
                            <dd class="col-sm-7" data-registration-member-name></dd>
                            <dt class="col-sm-5">E-Mail im Mitglied</dt>
                            <dd class="col-sm-7" data-registration-member-email></dd>
                            <dt class="col-sm-5">Registrierungs-/Login-E-Mail</dt>
                            <dd class="col-sm-7">{{ $registrationRequest->email }}</dd>
                        </dl>
                    </div>
                    <fieldset class="border rounded p-3 mb-3 d-none" data-registration-email-choice>
                        <legend class="h6 float-none w-auto px-2">Abweichende Kontakt-E-Mail</legend>
                        <p class="small text-secondary">Die Login-Adresse ist immer die bestätigte Registrierungsadresse. Entscheide zusätzlich, welche E-Mail im Mitgliedsstammsatz stehen soll.</p>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" id="member_email_keep" name="member_email_action" value="keep" @checked(old('member_email_action', 'keep') === 'keep')>
                            <label class="form-check-label" for="member_email_keep">
                                Bestehende Kontakt-E-Mail beibehalten
                                <span class="d-block small text-secondary" data-registration-existing-email></span>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" id="member_email_registration" name="member_email_action" value="use_registration" @checked(old('member_email_action') === 'use_registration')>
                            <label class="form-check-label" for="member_email_registration">
                                Registrierungsadresse als Kontakt-E-Mail übernehmen
                                <span class="d-block small text-secondary">{{ $registrationRequest->email }}</span>
                            </label>
                        </div>
                    </fieldset>
                    <input type="hidden" name="member_email_action" value="keep" data-registration-email-default>
                    @if ($registrationRequest->parcel_id && $candidates->isEmpty())
                        <div class="alert alert-danger">Es gibt kein freigabefähiges Mitglied. Prüfe zuerst Pächterhistorie und bestehende Kontoverknüpfungen.</div>
                    @endif
                    <label class="form-label" for="approval_review_note">Interner Prüfhinweis (optional)</label>
                    <input class="form-control" id="approval_review_note" name="review_note" maxlength="255">
                    <button class="btn btn-success mt-3" @disabled($registrationRequest->parcel_id && $candidates->isEmpty()) onclick="return confirm('Benutzerkonto verbindlich freigeben?')">Konto freigeben</button>
                </form>
            </div>
            <div class="col-lg-5">
                <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('registration-requests.reject', $registrationRequest) }}">
                    @csrf
                    <h2 class="h5">Anfrage ablehnen</h2>
                    <label class="form-label" for="rejection_review_note">Begründung</label>
                    <textarea class="form-control" id="rejection_review_note" name="review_note" rows="4" maxlength="255" required></textarea>
                    <div class="form-text">Zum Beispiel: Angaben stimmen nicht mit dem Pachtvertrag überein.</div>
                    <button class="btn btn-outline-danger mt-3" onclick="return confirm('Registrierungsanfrage wirklich ablehnen?')">Anfrage ablehnen</button>
                </form>
            </div>
        </div>
    @endcan
</div>
@endsection
