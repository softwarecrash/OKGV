@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2">{{ $poll->title }}</h1>
    <x-validation-errors />
    <p>{{ $poll->type->label() }} · {{ $poll->statusLabel() }} · {{ $poll->audience->label() }}</p>
    <p>Abstimmung: {{ $poll->starts_at->format('d.m.Y H:i') }} bis {{ $poll->ends_at->format('d.m.Y H:i') }} ({{ config('app.timezone') }})</p>
    <p>{{ $poll->results_visibility->label() }}. Antworten sind kontobezogen gespeichert, nicht anonym. Die Auswertung enthält keine Namensliste.</p>
    <div class="mb-4">{!! nl2br(e($poll->description)) !!}</div>
    @can('update', $poll)<a class="btn btn-outline-primary mb-3" href="{{ route('polls.edit', $poll) }}">Entwurf bearbeiten</a>@endcan
    @if ($participation?->answered_at)
        <section class="alert alert-success">
            <h2 class="h5">Deine Antwort ist gespeichert</h2>
            <p>Abgegeben am {{ $participation->answered_at->format('d.m.Y H:i') }}. Eine Änderung ist nicht mehr möglich.</p>
            <ul class="mb-0">
                @forelse ($poll->options->whereIn('id', $participation->option_ids ?? []) as $option)<li>{{ $option->label }}</li>@empty<li>Keine Auswahl / kein Termin passt</li>@endforelse
            </ul>
        </section>
    @endif
    @can('answer', $poll)
        <form method="POST" action="{{ route('polls.answer', $poll) }}" class="card card-body mb-4">
            @csrf
            <h2 class="h5">Deine Teilnahme <x-action-indicator :count="1" /></h2>
            <p>{{ $poll->multiple ? 'Du kannst mehrere passende Antworten markieren.' : 'Wähle eine Antwort.' }} Jede Person stimmt mit ihrem eigenen Konto einmal ab, unabhängig von der Anzahl ihrer Parzellen.</p>
            @foreach ($poll->options as $option)
                <div class="form-check mb-2">
                    <input class="form-check-input" type="{{ $poll->multiple ? 'checkbox' : 'radio' }}" name="option_ids[]" id="answer-{{ $option->id }}" value="{{ $option->id }}" @checked(in_array($option->id, old('option_ids', [])))>
                    <label class="form-check-label" for="answer-{{ $option->id }}">{{ $option->label }} @if ($option->starts_at) · {{ $option->starts_at->format('d.m.Y H:i') }} @if ($option->ends_at) bis {{ $option->ends_at->format('d.m.Y H:i') }} @endif @endif</label>
                </div>
            @endforeach
            <div class="form-check mt-2"><input class="form-check-input" type="checkbox" id="abstain" name="abstain" value="1" @checked(old('abstain'))><label class="form-check-label" for="abstain">Keine Auswahl / kein Termin passt (alle Antworten darüber frei lassen)</label></div>
            <div class="form-check mt-3"><input class="form-check-input" type="checkbox" id="confirmed" name="confirmed" value="1" required><label class="form-check-label" for="confirmed">Ich habe meine Auswahl geprüft. Meine Antwort wird verbindlich gespeichert und kann nicht geändert werden.</label></div>
            <button class="btn btn-primary align-self-start mt-3">Antwort abgeben</button>
        </form>
    @else
        <section class="card card-body mb-4">
            <h2 class="h5">Antwortmöglichkeiten</h2>
            <ul>@foreach ($poll->options as $option)<li>{{ $option->label }} @if ($option->starts_at) · {{ $option->starts_at->format('d.m.Y H:i') }} @if ($option->ends_at) bis {{ $option->ends_at->format('d.m.Y H:i') }} @endif @endif</li>@endforeach</ul>
            @unless ($participation?->answered_at)<p class="mb-0">Eine Teilnahme ist nur während des offenen Zeitraums mit einem eingeladenen, weiterhin berechtigten Konto möglich.</p>@endunless
        </section>
    @endcan
    @if ($results !== null)
        <section class="card card-body mb-4">
            <h2 class="h5">Ergebnis</h2>
            <p>{{ $results['answered'] }} Antworten von {{ $results['eligible'] }} eingeladenen Konten. Davon {{ $results['abstained'] }} ohne Auswahl. Bei Mehrfachauswahl kann die Summe der Stimmen größer sein als die Zahl der Antworten.</p>
            <div class="table-responsive"><table class="table"><thead><tr><th>Antwort</th><th>Termin</th><th>Stimmen</th></tr></thead><tbody>
                @foreach ($results['options'] as $option)<tr><td>{{ $option['label'] }}</td><td>{{ $option['starts_at'] }} @if ($option['ends_at']) bis {{ $option['ends_at'] }} @endif</td><td>{{ $option['count'] }}</td></tr>@endforeach
            </tbody></table></div>
            @can('export', $poll)<a href="{{ route('polls.export', $poll) }}" class="btn btn-outline-primary align-self-start">Ergebnis als CSV herunterladen</a>@endcan
        </section>
    @else
        <p class="alert alert-info">{{ $poll->isClosed() ? 'Die Ergebnisse dieser Abfrage sind nur für die Umfrageverwaltung sichtbar.' : 'Ergebnisse werden erst nach Abschluss der Abfrage angezeigt.' }}</p>
    @endif
    @foreach (['publish' => ['Veröffentlichen', 'Nach Veröffentlichung sind Fragen, Zeitraum und Zielgruppe unveränderbar. Die aktuell berechtigten Konten werden eingeladen.'], 'close' => ['Jetzt abschließen', 'Die Teilnahme endet sofort und kann nicht wieder geöffnet werden.'], 'archive' => ['Archivieren', 'Die Abfrage wird ins Archiv verschoben. Antworten bleiben erhalten.'], 'restore' => ['Aus Archiv holen', 'Die Abfrage wird wieder in der Übersicht geführt. Ein Abschluss wird nicht aufgehoben.']] as $action => [$label, $hint])
        @can($action, $poll)
            <form method="POST" action="{{ route('polls.'.$action, $poll) }}" class="card card-body mb-3">
                @csrf
                <div class="form-check mb-2"><input type="checkbox" name="confirmed" value="1" class="form-check-input" id="confirm-{{ $action }}" required><label class="form-check-label" for="confirm-{{ $action }}">{{ $hint }} Ich bestätige diese Aktion.</label></div>
                <button class="btn btn-outline-primary align-self-start">{{ $label }}</button>
            </form>
        @endcan
    @endforeach
    <a href="{{ route('polls.index') }}">Zur Übersicht</a>
</div>
@endsection
