@csrf
@if ($billingPeriod->exists)
    @method('PUT')
@endif

<x-validation-errors />

<div class="alert alert-info">
    Eine Abrechnungsperiode ist ein eindeutiger Rechnungslauf. Jeder Preis erhält zusätzlich seinen eigenen Leistungszeitraum, sodass Vorauszahlungen für das Folgejahr und Verbrauchskosten für das zurückliegende Jahr gemeinsam abgerechnet werden können. Änderungen an einem berechneten Zwischenstand verwerfen die bisherigen Rechnungsentwürfe.
</div>

<div class="mb-3">
    <label class="form-label" for="name">Bezeichnung</label>
    <input class="form-control" id="name" name="name" maxlength="255" required
           value="{{ old('name', $billingPeriod->name) }}" placeholder="Abrechnung 2026">
    <div class="form-text">Eine verständliche Bezeichnung, die später auch bei Rechnungen angezeigt wird.</div>
</div>
<div class="row g-3"
     x-data="{
         endsAt: @js(old('ends_at', $billingPeriod->ends_at?->format('Y-m-d'))),
         dueAt: @js(old('due_at', $billingPeriod->due_at?->format('Y-m-d'))),
         paymentTermDays: {{ (int) ($defaultPaymentTermDays ?? 14) }},
         suggestDueDate() {
             if (! this.endsAt) return;
             const date = new Date(this.endsAt + 'T00:00:00Z');
             date.setUTCDate(date.getUTCDate() + this.paymentTermDays);
             this.dueAt = date.toISOString().slice(0, 10);
         }
     }">
    <div class="col-md-4">
        <label class="form-label" for="starts_at">Beginn</label>
        <input class="form-control" type="date" id="starts_at" name="starts_at" required
               value="{{ old('starts_at', $billingPeriod->starts_at?->format('Y-m-d')) }}">
        <div class="form-text">Erster Tag des Rechnungslaufs. Der fachliche Leistungszeitraum wird später je Preis festgelegt.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="ends_at">Ende</label>
        <input class="form-control" type="date" id="ends_at" name="ends_at" required
               x-model="endsAt" @change="suggestDueDate()">
        <div class="form-text">Letzter Tag des Rechnungslaufs. Rechnungsläufe dürfen sich nicht überschneiden.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="due_at">Fälligkeit</label>
        <input class="form-control" type="date" id="due_at" name="due_at" required
               x-model="dueAt">
        <div class="form-text">Zahlungsfrist; beim Ändern des Enddatums werden {{ (int) ($defaultPaymentTermDays ?? 14) }} Tage vorgeschlagen.</div>
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button class="btn btn-primary">Speichern</button>
    <a class="btn btn-outline-secondary" href="{{ $billingPeriod->exists ? route('billing-periods.show', $billingPeriod) : route('billing-periods.index') }}">Abbrechen</a>
</div>
