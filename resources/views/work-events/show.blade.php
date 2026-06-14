@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">{{ $workEvent->title }}</h1>
            <p class="text-secondary mb-0">
                {{ $workEvent->starts_at->format('d.m.Y H:i') }} bis {{ $workEvent->ends_at->format('d.m.Y H:i') }}
                · {{ $workEvent->status->label() }}
            </p>
        </div>
        @can('update', $workEvent)
            <a class="btn btn-outline-primary" href="{{ route('work-events.edit', $workEvent) }}">Termin bearbeiten</a>
        @endcan
    </div>

    @if ($workEvent->status === App\Enums\WorkEventStatus::Planned && $workEvent->ends_at->isPast())
        <div class="alert alert-warning">
            Der Termin ist vorbei. Setze den Einsatz auf „Abgeschlossen“, um geleistete Stunden bestätigen zu können, oder auf „Abgesagt“.
        </div>
    @elseif ($workEvent->status === App\Enums\WorkEventStatus::Cancelled)
        <div class="alert alert-secondary">
            Dieser Einsatz wurde abgesagt. Seine Teilnehmerstunden werden nicht in Arbeitsstundenkonten übernommen.
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Abrechnungsperiode</dt><dd class="col-sm-9"><a href="{{ route('billing-periods.show', $workEvent->billingPeriod) }}">{{ $workEvent->billingPeriod->name }}</a></dd>
                <dt class="col-sm-3">Ort</dt><dd class="col-sm-9">{{ $workEvent->location ?: 'Nicht angegeben' }}</dd>
                <dt class="col-sm-3">Beschreibung</dt><dd class="col-sm-9">{!! nl2br(e($workEvent->description ?: 'Keine Beschreibung')) !!}</dd>
            </dl>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header">Teilnehmer</div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Mitglied</th><th>Parzelle</th><th>Status</th><th>Stunden</th><th>Hinweis</th><th></th></tr></thead>
                <tbody>
                    @forelse ($workEvent->participants->sortBy(fn ($entry) => $entry->member->last_name) as $participant)
                        <tr>
                            <td>
                                {{ $participant->member->full_name }}
                                <input form="participant-{{ $participant->id }}" type="hidden" name="member_id" value="{{ $participant->member_id }}">
                            </td>
                            <td>
                                Parzelle {{ $participant->parcel?->parcel_number }}
                                <input form="participant-{{ $participant->id }}" type="hidden" name="parcel_id" value="{{ $participant->parcel_id }}">
                            </td>
                            <td>
                                <select form="participant-{{ $participant->id }}" class="form-select form-select-sm" name="status" @disabled($workEvent->status === App\Enums\WorkEventStatus::Cancelled)>
                                    @foreach ($participantStatuses as $status)
                                        <option value="{{ $status->value }}" @selected($participant->status === $status)>{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input form="participant-{{ $participant->id }}" class="form-control form-control-sm" name="hours" type="number" min="0" step="0.25" value="{{ $participant->hours }}" @disabled($workEvent->status === App\Enums\WorkEventStatus::Cancelled)></td>
                            <td><input form="participant-{{ $participant->id }}" class="form-control form-control-sm" name="notes" maxlength="10000" value="{{ $participant->notes }}" @disabled($workEvent->status === App\Enums\WorkEventStatus::Cancelled)></td>
                            <td class="text-end">
                                <form id="participant-{{ $participant->id }}" method="POST" action="{{ route('work-event-participants.update', $participant) }}">
                                    @csrf
                                    @method('PUT')
                                    <button class="btn btn-sm btn-outline-primary" @disabled($workEvent->status === App\Enums\WorkEventStatus::Cancelled)>Speichern</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4">Noch keine Teilnehmer zugeordnet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($workEvent->billingPeriod->isEditable() && $workEvent->status !== App\Enums\WorkEventStatus::Cancelled)
        <div class="card border-0 shadow-sm">
            <div class="card-header">Teilnehmer hinzufügen</div>
            <div class="card-body">
                @if ($members->isEmpty())
                    <p class="text-secondary mb-0">Alle aktiven Mitglieder sind bereits zugeordnet.</p>
                @else
                    <form class="row g-3 align-items-end" method="POST" action="{{ route('work-events.participants.store', $workEvent) }}">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label" for="member_id">Mitglied</label>
                            <select class="form-select" id="member_id" name="member_id" required>
                                <option value="">Mitglied auswählen</option>
                                @foreach ($members as $member)
                                    <option value="{{ $member->id }}">{{ $member->last_name }}, {{ $member->first_name }} · {{ $member->member_number }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="parcel_id">Parzelle</label>
                            <select class="form-select" id="parcel_id" name="parcel_id" required>
                                <option value="">Parzelle auswählen</option>
                                @foreach ($parcels as $parcel)
                                    <option value="{{ $parcel->id }}">Parzelle {{ $parcel->parcel_number }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="participant-status">Status</label>
                            <select class="form-select" id="participant-status" name="status" required>
                                @foreach ($participantStatuses as $status)
                                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="hours">Stunden</label>
                            <input class="form-control" id="hours" name="hours" type="number" min="0" step="0.25" value="0" required>
                        </div>
                        <div class="col-md-1"><button class="btn btn-primary w-100">+</button></div>
                        <div class="col-12 form-text">Nur bestätigte Teilnahmen eines abgeschlossenen Einsatzes werden automatisch übernommen.</div>
                    </form>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
