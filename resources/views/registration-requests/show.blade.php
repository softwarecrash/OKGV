@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-4">Registrierungsanfrage prüfen</h1>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Angegebener Name</dt><dd class="col-sm-9">{{ $registrationRequest->full_name }}</dd>
                <dt class="col-sm-3">E-Mail</dt><dd class="col-sm-9">{{ $registrationRequest->email }}</dd>
                <dt class="col-sm-3">Parzelle</dt><dd class="col-sm-9">{{ $registrationRequest->parcel_number }}</dd>
                <dt class="col-sm-3">Status</dt><dd class="col-sm-9">{{ $registrationRequest->status->label() }}</dd>
                @if ($registrationRequest->reviewed_at)
                    <dt class="col-sm-3">Bearbeitet</dt><dd class="col-sm-9">{{ $registrationRequest->reviewed_at->format('d.m.Y H:i') }}{{ $registrationRequest->review_note ? ' · '.$registrationRequest->review_note : '' }}</dd>
                @endif
            </dl>
        </div>
    </div>

    @can('review', $registrationRequest)
        <div class="alert alert-warning">Vergleiche die Angaben mit dem Pachtvertrag oder einem anderen verlässlichen Vereinsnachweis. Wähle nur die tatsächlich anfragende Person aus.</div>
        <div class="row g-4">
            <div class="col-lg-7">
                <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('registration-requests.approve', $registrationRequest) }}">
                    @csrf
                    <x-validation-errors />
                    <h2 class="h5">Konto freigeben</h2>
                    <label class="form-label" for="member_id">Mitglied der Parzelle zuordnen</label>
                    <select class="form-select" id="member_id" name="member_id" required>
                        <option value="">Bitte auswählen</option>
                        @foreach ($candidates as $member)
                            <option value="{{ $member->id }}" @selected(old('member_id') == $member->id)>{{ $member->member_number }} · {{ $member->full_name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text mb-3">Angezeigt werden nur aktuelle Pächter dieser Parzelle ohne vorhandenes Benutzerkonto.</div>
                    @if ($candidates->isEmpty())
                        <div class="alert alert-danger">Es gibt kein freigabefähiges Mitglied. Prüfe zuerst Pächterhistorie und bestehende Kontoverknüpfungen.</div>
                    @endif
                    <label class="form-label" for="approval_review_note">Interner Prüfhinweis (optional)</label>
                    <input class="form-control" id="approval_review_note" name="review_note" maxlength="255">
                    <button class="btn btn-success mt-3" @disabled($candidates->isEmpty()) onclick="return confirm('Pächterkonto verbindlich freigeben?')">Konto freigeben</button>
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
