@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Datenschutz</h1>
            <p class="text-secondary mb-0">Datenauskunft, freiwillige Freigaben und nachvollziehbare Löschprüfung.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('privacy.information') }}">Datenschutzinformationen</a>
    </div>

    @if ($member)
        <div class="row g-4 mb-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h5">Daten für aktuelle Mitpächter freigeben</h2>
                        <p class="text-secondary">Ohne deine ausdrückliche Auswahl wird nichts angezeigt. Nur Personen, die aktuell gemeinsam mit dir in derselben Parzelle eingetragen sind, können freigegebene Angaben sehen. Du kannst die Einwilligung jederzeit widerrufen.</p>
                        <form method="POST" action="{{ route('privacy.settings.update') }}">
                            @csrf
                            @method('PUT')
                            @foreach ([
                                'share_name' => 'Vor- und Nachname',
                                'share_email' => 'E-Mail-Adresse',
                                'share_phone' => 'Telefonnummer',
                                'share_mobile' => 'Mobilnummer',
                                'share_address' => 'Postanschrift',
                            ] as $field => $label)
                                <input type="hidden" name="{{ $field }}" value="0">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="{{ $field }}" value="1" id="{{ $field }}" @checked(old($field, $privacySetting->{$field}))>
                                    <label class="form-check-label" for="{{ $field }}">{{ $label }}</label>
                                </div>
                            @endforeach
                            <button class="btn btn-primary mt-2" type="submit">Freigaben speichern</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h5">Datenauskunft und Löschung</h2>
                        <p class="text-secondary">Der JSON-Export enthält die zu deinem Mitgliedskonto gespeicherten strukturierten Daten. Dokumentdateien werden aus Sicherheitsgründen nicht automatisch beigefügt.</p>
                        <a class="btn btn-outline-primary mb-3" href="{{ route('privacy.export', $member) }}">Datenauskunft herunterladen</a>
                        <p class="small text-secondary">Eine Löschung wird gegen offene Vorgänge und die konfigurierte Mindestaufbewahrung von {{ $retentionYears }} Jahren geprüft.</p>
                        <form method="POST" action="{{ route('privacy-erasure-requests.store') }}" onsubmit="return confirm('Löschanfrage wirklich zur Prüfung einreichen?')">
                            @csrf
                            <button class="btn btn-outline-danger" type="submit">Löschprüfung beantragen</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h5">Freigaben deiner Mitpächter</h2>
                        <p class="text-secondary">Hier erscheinen ausschließlich freiwillig freigegebene Angaben aktuell gemeinsam eingetragener Pächter.</p>
                        @forelse ($coTenants as $coTenant)
                            @php($sharing = $coTenant->privacySetting)
                            <div class="border rounded p-3 mb-2">
                                <strong>{{ $sharing->share_name ? $coTenant->full_name : 'Mitpächter ohne Namensfreigabe' }}</strong>
                                <dl class="row mb-0 mt-2">
                                    @if ($sharing->share_email)<dt class="col-sm-3">E-Mail</dt><dd class="col-sm-9">{{ $coTenant->email ?: '–' }}</dd>@endif
                                    @if ($sharing->share_phone)<dt class="col-sm-3">Telefon</dt><dd class="col-sm-9">{{ $coTenant->phone ?: '–' }}</dd>@endif
                                    @if ($sharing->share_mobile)<dt class="col-sm-3">Mobil</dt><dd class="col-sm-9">{{ $coTenant->mobile ?: '–' }}</dd>@endif
                                    @if ($sharing->share_address)<dt class="col-sm-3">Anschrift</dt><dd class="col-sm-9">{{ $coTenant->street }}, {{ $coTenant->zip }} {{ $coTenant->city }}</dd>@endif
                                </dl>
                            </div>
                        @empty
                            <p class="mb-0 text-secondary">Aktuell liegen keine Freigaben von Mitpächtern vor.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @elseif (! auth()->user()->canManagePrivacy())
        <div class="alert alert-warning">Dein Benutzerkonto ist noch keinem Mitglied zugeordnet. Eine persönliche Datenauskunft ist deshalb noch nicht verfügbar.</div>
    @endif

    @if ($erasureRequests)
        <div class="card border-0 shadow-sm">
            <div class="card-header">{{ auth()->user()->canManagePrivacy() ? 'Löschanfragen verwalten' : 'Meine Löschanfragen' }}</div>
            <div class="card-body">
                @forelse ($erasureRequests as $erasureRequest)
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <div>
                                @if (auth()->user()->canManagePrivacy())
                                    <strong>{{ $erasureRequest->member->full_name }}</strong>
                                    <span class="text-secondary"> · {{ $erasureRequest->member->member_number }}</span>
                                @endif
                                <div>{{ $erasureRequest->status->label() }} · {{ $erasureRequest->requested_at->format('d.m.Y H:i') }}</div>
                            </div>
                            @if (auth()->user()->canManagePrivacy())
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('privacy.export', $erasureRequest->member) }}">Auskunft</a>
                            @endif
                        </div>
                        @if ($erasureRequest->blockers)
                            <div class="alert alert-warning mt-3 mb-0">
                                <strong>Aufbewahrungs- oder Bearbeitungsgründe:</strong>
                                <ul class="mb-0">
                                    @foreach ($erasureRequest->blockers as $blocker)
                                        <li>{{ $blocker }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if ($erasureRequest->review_note)
                            <p class="mt-3 mb-0"><strong>Prüfvermerk:</strong> {{ $erasureRequest->review_note }}</p>
                        @endif
                        @can('review', $erasureRequest)
                            <form class="mt-3" method="POST" action="{{ route('privacy-erasure-requests.review', $erasureRequest) }}">
                                @csrf
                                <label class="form-label" for="review_note_{{ $erasureRequest->id }}">Prüfvermerk</label>
                                <textarea class="form-control" id="review_note_{{ $erasureRequest->id }}" name="review_note" rows="2">{{ old('review_note') }}</textarea>
                                <div class="form-text">Die Prüfung ermittelt offene Vorgänge und Aufbewahrungsgründe neu.</div>
                                <button class="btn btn-outline-primary mt-2" type="submit">Löschbarkeit prüfen</button>
                            </form>
                        @endcan
                        @can('anonymize', $erasureRequest)
                            @if ($erasureRequest->status === App\Enums\PrivacyErasureStatus::Ready)
                                <form class="mt-3 border border-danger rounded p-3" method="POST" action="{{ route('privacy-erasure-requests.anonymize', $erasureRequest) }}">
                                    @csrf
                                    <h3 class="h6 text-danger">Endgültige Pseudonymisierung</h3>
                                    <p class="small">Diese Aktion entfernt Kontaktdaten und sperrt das verknüpfte Pächterkonto. Historische Fachdatensätze bleiben unter einer anonymen Referenz erhalten.</p>
                                    <label class="form-label" for="current_password_{{ $erasureRequest->id }}">Aktuelles Administratorpasswort</label>
                                    <input class="form-control mb-2" type="password" id="current_password_{{ $erasureRequest->id }}" name="current_password" required autocomplete="current-password">
                                    <label class="form-label" for="confirmation_{{ $erasureRequest->id }}">Zur Bestätigung PSEUDONYMISIEREN eingeben</label>
                                    <input class="form-control" id="confirmation_{{ $erasureRequest->id }}" name="confirmation" required autocomplete="off">
                                    <button class="btn btn-danger mt-2" type="submit">Personendaten pseudonymisieren</button>
                                </form>
                            @endif
                        @endcan
                    </div>
                @empty
                    <p class="mb-0 text-secondary">Keine Löschanfragen vorhanden.</p>
                @endforelse
                {{ $erasureRequests->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
