@extends('layouts.app')

@section('content')
<div class="container">
    <div class="mb-4">
        <h1 class="h2 mb-1">Pächterwechsel durchführen</h1>
        <p class="text-secondary mb-0">Der Wechsel wird vollständig und unveränderlich historisiert.</p>
    </div>

    @if ($parcels->isEmpty())
        <div class="alert alert-warning">
            <strong>Keine aktuell verpachtete Parzelle gefunden.</strong>
            <div>Ein Pächterwechsel benötigt eine Parzelle mit einem derzeit eingetragenen Hauptpächter.</div>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('tenant-transitions.index') }}">Zurück</a>
    @else
        <form method="POST"
              action="{{ route('tenant-transitions.store') }}"
              enctype="multipart/form-data"
              class="card border-0 shadow-sm"
              x-data="{
                  selectedParcel: '{{ old('parcel_id', $selectedParcelId) }}',
                  transferDate: '{{ old('transfer_date', today()->format('Y-m-d')) }}',
                  meterApplies(installedAt, removedAt) {
                      return installedAt <= this.transferDate && (!removedAt || removedAt >= this.transferDate);
                  }
              }">
            @csrf
            <div class="card-body">
                <x-validation-errors />

                <div class="alert alert-warning">
                    <strong>Bitte vor dem Abschluss sorgfältig prüfen.</strong>
                    <div>Der bisherige Vertrag endet am Vortag. Neue Vertragsparteien beginnen am Übergabetag. Dieser Vorgang kann anschließend nicht bearbeitet oder gelöscht werden.</div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="parcel_id">Parzelle</label>
                        <select class="form-select" id="parcel_id" name="parcel_id" x-model="selectedParcel" required>
                            @foreach ($parcels as $parcel)
                                <option value="{{ $parcel->id }}">Parzelle {{ $parcel->parcel_number }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Es werden alle am Vortag aktiven Vertragsparteien dieser Parzelle beendet.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="transfer_date">Übergabedatum</label>
                        <input class="form-control" id="transfer_date" name="transfer_date" type="date" max="{{ today()->format('Y-m-d') }}" x-model="transferDate" required>
                        <div class="form-text">Eine Übergabe kann am tatsächlichen Tag oder nachträglich dokumentiert werden, nicht im Voraus.</div>
                    </div>

                    @foreach ($parcels as $parcel)
                        <div class="col-12" x-show="selectedParcel === '{{ $parcel->id }}'">
                            <div class="rounded border p-3 bg-body-tertiary">
                                <strong>Bisherige Vertragsparteien</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach ($parcel->tenancies as $tenancy)
                                        <li>
                                            {{ $tenancy->member->full_name }}
                                            @if ($tenancy->is_primary)
                                                <span class="badge text-bg-primary">Hauptpächter</span>
                                            @else
                                                <span class="badge text-bg-secondary">Mitpächter</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endforeach

                    <div class="col-md-6">
                        <label class="form-label" for="incoming_primary_member_id">Neuer Hauptpächter</label>
                        <select class="form-select" id="incoming_primary_member_id" name="incoming_primary_member_id" required>
                            <option value="">Bitte auswählen</option>
                            @foreach ($members as $member)
                                <option value="{{ $member->id }}" @selected((string) old('incoming_primary_member_id') === (string) $member->id)>
                                    {{ $member->member_number }} · {{ $member->full_name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Diese Person wird Hauptempfänger künftiger parzellenbezogener Rechnungen.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="incoming_co_member_ids">Weitere Vertragsparteien</label>
                        <select class="form-select" id="incoming_co_member_ids" name="incoming_co_member_ids[]" multiple size="5">
                            @foreach ($members as $member)
                                <option value="{{ $member->id }}" @selected(in_array((string) $member->id, array_map('strval', old('incoming_co_member_ids', [])), true))>
                                    {{ $member->member_number }} · {{ $member->full_name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Mit Strg beziehungsweise Cmd können mehrere Mitpächter ausgewählt werden.</div>
                    </div>

                    <div class="col-12">
                        <h2 class="h5 mt-2">Zählerstände zur Übergabe</h2>
                        <p class="text-secondary">Für jeden am Übergabetag vorhandenen Zähler ist ein Stand notwendig. Dadurch kann der Verbrauch korrekt zwischen alter und neuer Pacht aufgeteilt werden.</p>
                    </div>
                    @foreach ($parcels as $parcel)
                        <div class="col-12" x-show="selectedParcel === '{{ $parcel->id }}'">
                            @forelse ($parcel->meters as $meter)
                                @php
                                    $lastReading = $meter->readings->first();
                                    $lastValue = $lastReading?->effective_reading_value ?? $meter->start_reading;
                                @endphp
                                <div class="row align-items-end g-2 mb-3"
                                     x-show="meterApplies('{{ $meter->installed_at->format('Y-m-d') }}', '{{ $meter->removed_at?->format('Y-m-d') }}')">
                                    <div class="col-md-7">
                                        <label class="form-label" for="meter-{{ $meter->id }}">
                                            {{ $meter->type->label() }} · {{ $meter->meter_number }}
                                        </label>
                                        <div class="small text-secondary">
                                            Zur Orientierung letzter gespeicherter Stand: {{ number_format((float) $lastValue, 4, ',', '.') }}
                                            @if ($lastReading)
                                                vom {{ $lastReading->reading_date->format('d.m.Y') }}
                                            @else
                                                (Einbaustand)
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="input-group">
                                            <input class="form-control"
                                                   id="meter-{{ $meter->id }}"
                                                   name="meter_readings[{{ $meter->id }}]"
                                                   type="number"
                                                   min="0"
                                                   step="0.0001"
                                                   placeholder="{{ $lastValue }}"
                                                   value="{{ old("meter_readings.{$meter->id}") }}"
                                                   :disabled="selectedParcel !== '{{ $parcel->id }}' || !meterApplies('{{ $meter->installed_at->format('Y-m-d') }}', '{{ $meter->removed_at?->format('Y-m-d') }}')"
                                                   required>
                                            <span class="input-group-text">{{ $meter->type->unit() }}</span>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="alert alert-light border">Für diese Parzelle sind keine aktiven Zähler vorhanden.</div>
                            @endforelse
                        </div>
                    @endforeach

                    <div class="col-md-6">
                        <label class="form-label" for="photos">Übergabefotos (optional)</label>
                        <input class="form-control" id="photos" name="photos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple>
                        <div class="form-text">Bis zu 10 JPEG-, PNG- oder WebP-Dateien, jeweils höchstens 8 MiB.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="documents">Übergabedokumente (optional)</label>
                        <input class="form-control" id="documents" name="documents[]" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,.txt,.docx,.xlsx" multiple>
                        <div class="form-text">Bis zu 10 private Dokumente, jeweils höchstens 20 MiB.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="notes">Interne Übergabenotizen</label>
                        <textarea class="form-control" id="notes" name="notes" rows="4" maxlength="10000">{{ old('notes') }}</textarea>
                        <div class="form-text">Nur für berechtigte Vereinskonten sichtbar.</div>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" id="confirm_open_claims" name="confirm_open_claims" type="checkbox" value="1" @checked(old('confirm_open_claims')) required>
                            <label class="form-check-label" for="confirm_open_claims">
                                Ich habe verstanden, dass offene Forderungen bei den bisherigen Vertragsparteien verbleiben und nicht auf neue Pächter übertragen werden.
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex flex-wrap gap-2">
                <button class="btn btn-primary" type="submit">Pächterwechsel verbindlich abschließen</button>
                <a class="btn btn-outline-secondary" href="{{ route('tenant-transitions.index') }}">Abbrechen</a>
            </div>
        </form>
    @endif
</div>
@endsection
