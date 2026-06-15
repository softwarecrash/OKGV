@extends('layouts.app')

@section('content')
<div class="container">
    <div class="mb-4">
        <h1 class="h2 mb-1">Nummernkreise</h1>
        <p class="text-secondary mb-0">Automatische Nummern für neue Mitglieder, Rechnungen, SEPA-Mandate und Dokumente festlegen.</p>
    </div>

    <x-validation-errors />

    <div class="alert alert-info">
        <strong>So funktionieren die Formate:</strong>
        <code>{NUMMER}</code> steht für die fortlaufende Nummer, <code>{JAHR}</code> für das vierstellige Jahr.
        Bereits vergebene Nummern werden nicht geändert. Manuell angelegte oder importierte Nummern werden beim Weiterzählen automatisch übersprungen.
    </div>

    <form method="POST" action="{{ route('number-sequences.update') }}">
        @csrf
        @method('PUT')

        <div class="row g-4">
            @foreach ($types as $type)
                @php($sequence = $sequences->get($type->value))
                <div class="col-xl-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h2 class="h5">{{ $type->label() }}</h2>
                            <p class="text-secondary">
                                Nächste Vorschau:
                                <strong>{{ $manager->preview($sequence) }}</strong>
                            </p>

                            <div class="mb-3">
                                <label class="form-label" for="format_{{ $type->value }}">Format</label>
                                <input
                                    class="form-control font-monospace"
                                    id="format_{{ $type->value }}"
                                    name="sequences[{{ $type->value }}][format]"
                                    value="{{ old("sequences.{$type->value}.format", $sequence->format) }}"
                                    maxlength="100"
                                    required>
                                <div class="form-text">Beispiel: <code>{{ $type->defaultFormat() }}</code>. Leerzeichen werden automatisch durch Bindestriche ersetzt.</div>
                            </div>

                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label" for="padding_{{ $type->value }}">Mindeststellen der Nummer</label>
                                    <input
                                        class="form-control"
                                        type="number"
                                        id="padding_{{ $type->value }}"
                                        name="sequences[{{ $type->value }}][padding]"
                                        value="{{ old("sequences.{$type->value}.padding", $sequence->padding) }}"
                                        min="1"
                                        max="12"
                                        required>
                                    <div class="form-text">Aus 23 wird bei fünf Stellen beispielsweise 00023.</div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label" for="next_value_{{ $type->value }}">Nächster Zählerstand</label>
                                    <input
                                        class="form-control"
                                        type="number"
                                        id="next_value_{{ $type->value }}"
                                        name="sequences[{{ $type->value }}][next_value]"
                                        value="{{ old("sequences.{$type->value}.next_value", $sequence->next_value) }}"
                                        min="1"
                                        max="999999999999"
                                        required>
                                    <div class="form-text">Belegte Nummern werden übersprungen. Dadurch können nachvollziehbare Lücken entstehen.</div>
                                </div>
                            </div>

                            <input type="hidden" name="sequences[{{ $type->value }}][reset_yearly]" value="0">
                            <div class="form-check form-switch mt-3">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    role="switch"
                                    id="reset_yearly_{{ $type->value }}"
                                    name="sequences[{{ $type->value }}][reset_yearly]"
                                    value="1"
                                    @checked(old("sequences.{$type->value}.reset_yearly", $sequence->reset_yearly))>
                                <label class="form-check-label" for="reset_yearly_{{ $type->value }}">Zähler jedes Kalenderjahr bei 1 beginnen</label>
                                <div class="form-text">Bei aktiviertem Jahreswechsel muss das Format <code>{JAHR}</code> enthalten.</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary" type="submit">Nummernkreise speichern</button>
            <a class="btn btn-outline-secondary" href="{{ route('application-settings.edit') }}">Zur globalen Konfiguration</a>
        </div>
    </form>
</div>
@endsection
