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
                @if ($actionIndicators['meter_readings'] > 0)
                    <a class="alert-link" href="{{ route('meter-reading-submissions.index') }}">
                        {{ $actionIndicators['meter_readings'] }}
                        {{ $actionIndicators['meter_readings'] === 1 ? 'abgelehnte Zählerstandsmeldung' : 'abgelehnte Zählerstandsmeldungen' }}
                    </a>
                @endif
                @if ($actionIndicators['work_hour_submissions'] > 0)
                    <a class="alert-link" href="{{ route('work-hour-submissions.index') }}">
                        {{ $actionIndicators['work_hour_submissions'] }}
                        {{ $actionIndicators['work_hour_submissions'] === 1 ? 'abgelehnte Arbeitsstundenmeldung' : 'abgelehnte Arbeitsstundenmeldungen' }}
                    </a>
                @endif
                @if ($actionIndicators['invoices'] > 0)
                    <a class="alert-link" href="{{ route('invoices.index') }}">
                        {{ $actionIndicators['invoices'] }}
                        {{ $actionIndicators['invoices'] === 1 ? 'offene Rechnung' : 'offene Rechnungen' }}
                    </a>
                @endif
            </div>
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
                        <p class="mb-0">{{ $member->street }}<br>{{ $member->zip }} {{ $member->city }}</p>
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
                            <a href="{{ route('invoices.index') }}">Alle</a>
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
                            <a href="{{ route('meter-reading-submissions.index') }}">Alle</a>
                        </div>
                        @forelse ($submissions as $submission)
                            <div class="border-top py-2">{{ $submission->meter->type->label() }} · {{ $submission->reading_date->format('d.m.Y') }} · {{ $submission->reading_value }} · <strong>{{ $submission->status->label() }}</strong></div>
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
                            <a href="{{ route('work-hour-submissions.index') }}">Alle</a>
                        </div>
                        @forelse ($workHourSubmissions as $submission)
                            <div class="border-top py-2">
                                Parzelle {{ $submission->parcel->parcel_number }} ·
                                {{ $submission->worked_at->format('d.m.Y') }} ·
                                {{ number_format((float) $submission->hours, 2, ',', '.') }} Std. ·
                                <strong>{{ $submission->status->label() }}</strong>
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
