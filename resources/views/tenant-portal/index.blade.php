@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-2">Mein Pächterportal</h1>
    <p class="text-secondary mb-4">Hier findest du ausschließlich die Daten, die deinem eigenen Mitgliedskonto zugeordnet sind.</p>

    @if ($actionIndicators['total'] > 0)
        <div class="alert alert-warning" role="status">
            <strong>
                {{ $actionIndicators['total'] }}
                {{ $actionIndicators['total'] === 1 ? 'Aufgabe benötigt' : 'Aufgaben benötigen' }}
                deine Aufmerksamkeit.
            </strong>
            <div class="d-flex flex-wrap gap-2 mt-2">
                @if ($actionIndicators['announcements'] > 0)
                    <a class="alert-link" href="{{ route('announcements.index', ['unread' => 1]) }}">{{ $actionIndicators['announcements'] }} ungelesene Bekanntmachungen</a>
                @endif
                @if ($actionIndicators['polls'] > 0)
                    <a class="alert-link" href="{{ route('polls.index', ['unanswered' => 1]) }}">{{ $actionIndicators['polls'] }} {{ $actionIndicators['polls'] === 1 ? 'offene Umfrage' : 'offene Umfragen' }}</a>
                @endif
                @if ($actionIndicators['meter_readings'] > 0)
                    <a class="alert-link" href="{{ route('meter-reading-submissions.index', ['own' => 1]) }}">
                        {{ $actionIndicators['meter_readings'] }}
                        {{ $actionIndicators['meter_readings'] === 1 ? 'abgelehnte Zählerstandsmeldung' : 'abgelehnte Zählerstandsmeldungen' }}
                    </a>
                @endif
                @if ($actionIndicators['work_hour_submissions'] > 0)
                    <a class="alert-link" href="{{ route('work-hour-submissions.index', ['own' => 1]) }}">
                        {{ $actionIndicators['work_hour_submissions'] }}
                        {{ $actionIndicators['work_hour_submissions'] === 1 ? 'abgelehnte Arbeitsstundenmeldung' : 'abgelehnte Arbeitsstundenmeldungen' }}
                    </a>
                @endif
                @if ($actionIndicators['invoices'] > 0)
                    <a class="alert-link" href="{{ route('invoices.index', ['own' => 1]) }}">
                        {{ $actionIndicators['invoices'] }}
                        {{ $actionIndicators['invoices'] === 1 ? 'offene Rechnung' : 'offene Rechnungen' }}
                    </a>
                @endif
                @if ($actionIndicators['garden_inspections'] > 0)
                    <a class="alert-link" href="{{ route('garden-inspections.index') }}">
                        {{ $actionIndicators['garden_inspections'] }} {{ $actionIndicators['garden_inspections'] === 1 ? 'offene Gartenfeststellung' : 'offene Gartenfeststellungen' }}
                    </a>
                @endif
            </div>
        </div>
    @endif

    @if (App\Enums\FeatureModule::Announcements->enabled())
        <div class="card card-body border-0 shadow-sm mb-4">
            <h2 class="h5">Schwarzes Brett <x-action-indicator :count="$actionIndicators['announcements']" label="ungelesene Bekanntmachungen" /></h2>
            <p>Neuigkeiten und wichtige Mitteilungen deines Vereins.</p>
            <div><a class="btn btn-outline-primary" href="{{ route('announcements.index') }}">Beiträge ansehen</a></div>
        </div>
    @endif

    @if (App\Enums\FeatureModule::Polls->enabled() && auth()->user()->can('viewAny', App\Models\Poll::class))
        <div class="card card-body border-0 shadow-sm mb-4">
            <h2 class="h5">Umfragen und Terminabfragen <x-action-indicator :count="$actionIndicators['polls']" label="offene Umfragen" /></h2>
            <p>Gib deine Rückmeldung zu aktuellen Vereinsfragen oder passenden Terminen ab.</p>
            <div><a class="btn btn-outline-primary" href="{{ route('polls.index', $actionIndicators['polls'] > 0 ? ['unanswered' => 1] : []) }}">Umfragen ansehen</a></div>
        </div>
    @endif

    @if (App\Enums\FeatureModule::GardenInspections->enabled())
        <div class="card card-body border-0 shadow-sm mb-4">
            <h2 class="h5">Gartenbegehungen <x-action-indicator :count="$actionIndicators['garden_inspections']" label="offene Gartenfeststellungen" /></h2>
            <p>Hinweise und Fristen aus Begehungen deiner aktuellen Parzellen.</p>
            <div><a class="btn btn-outline-primary" href="{{ route('garden-inspections.index') }}">Hinweise ansehen</a></div>
        </div>
    @endif

    @unless ($member)
        <div class="alert alert-warning">Dein Benutzerkonto ist noch keinem Mitglied zugeordnet. Bitte wende dich an den Vorstand.</div>
    @else
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h5">Meine Daten</h2>
                        <p class="mb-1"><strong>{{ $member->full_name }}</strong></p>
                        <p class="mb-1">Mitgliedsnummer {{ $member->member_number }}</p>
                        <p>{{ $member->street }}<br>{{ $member->zip }} {{ $member->city }}</p>
                        @if (App\Enums\FeatureModule::Sepa->enabled())
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('tenant-portal.sepa-mandates.index') }}">
                                SEPA-Mandate verwalten
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h5">Meine aktuellen Parzellen und Zähler</h2>
                        @forelse ($member->parcelTenancies as $tenancy)
                            <div class="border rounded p-3 mb-3">
                                <strong>Parzelle {{ $tenancy->parcel->parcel_number }}</strong>
                                <span class="text-secondary"> · {{ number_format((float) $tenancy->parcel->area_sqm, 2, ',', '.') }} m²</span>
                                <div class="mt-2 d-flex flex-wrap gap-2">
                                    @if (App\Enums\FeatureModule::WorkHours->enabled())
                                        <a class="btn btn-sm btn-outline-success" href="{{ route('work-hour-submissions.create', ['parcel_id' => $tenancy->parcel_id]) }}">
                                            Arbeitsstunden melden
                                        </a>
                                    @endif
                                    @if (App\Enums\FeatureModule::Meters->enabled())
                                    @forelse ($tenancy->parcel->meters as $meter)
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('meter-reading-submissions.create', $meter) }}">
                                            {{ $meter->type->label() }} {{ $meter->meter_number }} melden
                                        </a>
                                    @empty
                                        <span class="text-secondary">Keine Zähler hinterlegt.</span>
                                    @endforelse
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-secondary mb-0">Keine aktuelle Parzelle zugeordnet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
            @if (App\Enums\FeatureModule::Billing->enabled())
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="h5">
                                Letzte Rechnungen
                                <x-action-indicator :count="$actionIndicators['invoices']" label="offene Rechnungen" />
                            </h2>
                            <a href="{{ route('invoices.index', ['own' => 1]) }}">Alle eigenen</a>
                        </div>
                        @forelse ($invoices as $invoice)
                            <div class="d-flex justify-content-between border-top py-2">
                                <a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a>
                                <span>{{ number_format((float) $invoice->total_amount, 2, ',', '.') }} €</span>
                            </div>
                        @empty
                            <p class="text-secondary mb-0">Noch keine freigegebenen Rechnungen.</p>
                        @endforelse
                    </div>
                </div>
            </div>
            @endif
            @if (App\Enums\FeatureModule::Documents->enabled())
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center"><h2 class="h5">Meine Dokumente</h2><a href="{{ route('tenant-portal.documents') }}">Alle</a></div>
                        @forelse ($documents as $document)
                            <div class="border-top py-2"><a href="{{ route('tenant-portal.documents.download', $document) }}">{{ $document->title }}</a></div>
                        @empty
                            <p class="text-secondary mb-0">Noch keine Dokumente für dich freigegeben.</p>
                        @endforelse
                    </div>
                </div>
            </div>
            @endif
            @if (App\Enums\FeatureModule::Meters->enabled())
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="h5">
                                Letzte Zählerstandsmeldungen
                                <x-action-indicator :count="$actionIndicators['meter_readings']" label="abgelehnte Zählerstandsmeldungen" />
                            </h2>
                            <a href="{{ route('meter-reading-submissions.index', ['own' => 1]) }}">Alle eigenen</a>
                        </div>
                        @forelse ($submissions as $submission)
                            <div class="border-top py-2">
                                {{ $submission->meter->type->label() }} · {{ $submission->reading_date->format('d.m.Y') }} · {{ $submission->reading_value }} · <strong>{{ $submission->status->label() }}</strong>
                                @if ($submission->status === App\Enums\MeterReadingSubmissionStatus::Rejected)
                                    <div class="small text-danger mt-1"><strong>Ablehnungsgrund:</strong> {{ $submission->review_note }}</div>
                                @endif
                            </div>
                        @empty
                            <p class="text-secondary mb-0">Du hast noch keinen Zählerstand gemeldet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
            @endif
            @if (App\Enums\FeatureModule::WorkHours->enabled())
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="h5">
                                Letzte Arbeitsstundenmeldungen
                                <x-action-indicator :count="$actionIndicators['work_hour_submissions']" label="abgelehnte Arbeitsstundenmeldungen" />
                            </h2>
                            <a href="{{ route('work-hour-submissions.index', ['own' => 1]) }}">Alle eigenen</a>
                        </div>
                        @forelse ($workHourSubmissions as $submission)
                            <div class="border-top py-2">
                                Parzelle {{ $submission->parcel->parcel_number }} ·
                                {{ $submission->worked_at->format('d.m.Y') }} ·
                                {{ number_format((float) $submission->hours, 2, ',', '.') }} Std. ·
                                <strong>{{ $submission->status->label() }}</strong>
                                @if ($submission->status === App\Enums\WorkHourSubmissionStatus::Rejected)
                                    <div class="small text-danger mt-1">
                                        <strong>Ablehnungsgrund:</strong>
                                        {{ $submission->review_note ?: 'Kein Ablehnungsgrund hinterlegt.' }}
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-secondary mb-0">Du hast noch keine Arbeitsstunden gemeldet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
            @endif
        </div>
    @endunless
</div>
@endsection
