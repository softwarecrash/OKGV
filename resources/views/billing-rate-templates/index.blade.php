@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Preisvorlagen</h1>
            <p class="text-secondary mb-0">Wiederkehrende Kostenarten für neue Abrechnungsperioden vorbereiten.</p>
        </div>
        @can('create', App\Models\BillingRateTemplate::class)
            <a class="btn btn-primary" href="{{ route('billing-rate-templates.create') }}">Vorlage anlegen</a>
        @endcan
    </div>

    <div class="alert alert-info">
        Vorlagen vereinfachen die jährliche Abrechnung. Beim Übernehmen werden Bezeichnung und Berechnungsregeln kopiert; der Betrag kann für die neue Periode angepasst werden.
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Bezeichnung</th>
                        <th>Berechnung</th>
                        <th>Geltung</th>
                        <th>Abrechnung</th>
                        <th>Vorschlag</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($templates as $template)
                        <tr>
                            <td><strong>{{ $template->name }}</strong><br><code>{{ $template->code }}</code></td>
                            <td>{{ $template->calculation_type->label() }}</td>
                            <td>{{ $template->scope->label() }}</td>
                            <td>
                                {{ $template->settlement_type->label() }}
                                @if ($template->prorate)
                                    <br><span class="small text-secondary">taggenau anteilig</span>
                                @endif
                            </td>
                            <td>{{ $template->default_amount !== null ? number_format((float) $template->default_amount, 4, ',', '.').' €' : 'Bei Übernahme eingeben' }}</td>
                            <td>{{ $template->is_active ? 'Aktiv' : 'Inaktiv' }}</td>
                            <td class="text-end">
                                @can('update', $template)
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('billing-rate-templates.edit', $template) }}">Bearbeiten</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <strong>Noch keine Preisvorlagen vorhanden.</strong><br>
                                <span class="text-secondary">Lege häufig verwendete Kostenarten wie Pacht, Wasser oder Mitgliedsbeitrag einmalig als Vorlage an.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
