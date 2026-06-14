@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-4">Preis für {{ $billingPeriod->name }} anlegen</h1>
    @if (! $selectedTemplate)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header">Preisvorlage auswählen</div>
            <div class="card-body">
                <p class="text-secondary">Wähle eine vorbereitete Kostenart. Danach musst du nur noch den Betrag für diese Abrechnungsperiode prüfen oder ändern.</p>
                <div class="row g-3">
                    @forelse ($templates as $template)
                        <div class="col-md-6 col-xl-4">
                            <a class="card h-100 text-decoration-none" href="{{ route('billing-periods.billing-rates.create', [$billingPeriod, 'template' => $template->id]) }}">
                                <div class="card-body">
                                    <strong>{{ $template->name }}</strong>
                                    <div class="small text-secondary mt-1">
                                        {{ $template->calculation_type->label() }} · {{ $template->scope->label() }}
                                    </div>
                                    <div class="mt-2">
                                        {{ $template->default_amount !== null ? number_format((float) $template->default_amount, 4, ',', '.').' € vorgeschlagen' : 'Betrag bei Übernahme eingeben' }}
                                    </div>
                                </div>
                            </a>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="alert alert-warning mb-0">Es sind noch keine aktiven Preisvorlagen vorhanden. Berechtigte Vorstands- oder Administratorkonten können Vorlagen in der Vorlagenverwaltung anlegen.</div>
                        </div>
                    @endforelse
                </div>
                <div class="mt-3">
                    <a class="btn btn-outline-secondary" href="#manual-price">Preis ohne Vorlage vollständig eingeben</a>
                    <a class="btn btn-outline-primary" href="{{ route('billing-rate-templates.index') }}">Preisvorlagen verwalten</a>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-success">
            Vorlage <strong>{{ $selectedTemplate->name }}</strong> ausgewählt. Die Berechnungsregeln werden unverändert übernommen; passe unten nur den Betrag für {{ $billingPeriod->name }} an.
        </div>
    @endif
    <div class="card border-0 shadow-sm">
        <div class="card-body" id="manual-price">
            <form method="POST" action="{{ route('billing-periods.billing-rates.store', $billingPeriod) }}">
                @include('billing-rates._form')
            </form>
        </div>
    </div>
</div>
@endsection
