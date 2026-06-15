<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Rechnung {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 22mm 18mm; }
        body { color: #263238; font-family: "DejaVu Sans", sans-serif; font-size: 10pt; }
        h1 { color: #2E7D32; font-size: 22pt; margin: 0 0 6mm; }
        .muted { color: #607d8b; }
        .draft { border: 2px solid #b26a00; color: #8a5200; font-size: 15pt; font-weight: bold; margin-bottom: 8mm; padding: 3mm; text-align: center; }
        .columns { margin: 8mm 0; width: 100%; }
        .columns td { vertical-align: top; width: 50%; }
        table.items { border-collapse: collapse; margin-top: 10mm; width: 100%; }
        table.items th, table.items td { border-bottom: 1px solid #d7ddd7; padding: 2.5mm 1.5mm; }
        table.items th { background: #f5f7f5; color: #2E7D32; text-align: left; }
        .number { text-align: right; white-space: nowrap; }
        .total td { border-top: 2px solid #2E7D32; font-size: 12pt; font-weight: bold; }
        .footer { bottom: 8mm; color: #607d8b; font-size: 8pt; position: fixed; text-align: center; width: 100%; }
    </style>
</head>
<body>
    @if ($invoice->status === App\Enums\InvoiceStatus::Draft)
        <div class="draft">ZWISCHENSTAND – NICHT FREIGEGEBEN</div>
    @endif

    <h1>{{ config('app.name', 'OKGV') }}</h1>
    <div class="muted">Open Kleingarten Verwaltung</div>

    <table class="columns">
        <tr>
            <td>
                <strong>Rechnung an</strong><br>
                @foreach ($invoice->recipients as $recipient)
                    {{ $recipient->full_name }}<br>
                @endforeach
                @php($primaryRecipient = $invoice->primaryRecipient())
                @if ($primaryRecipient)
                    {{ $primaryRecipient->street }}<br>
                    {{ $primaryRecipient->zip }} {{ $primaryRecipient->city }}
                @endif
            </td>
            <td>
                <strong>Rechnungsnummer:</strong> {{ $invoice->invoice_number }}<br>
                <strong>Rechnungsdatum:</strong> {{ $invoice->issued_at->format('d.m.Y') }}<br>
                <strong>Fällig am:</strong> {{ $invoice->due_at->format('d.m.Y') }}<br>
                <strong>Abrechnungsperiode:</strong> {{ $invoice->billingPeriod->name }}
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Position</th>
                <th class="number">Menge</th>
                <th class="number">Einzelpreis</th>
                <th class="number">Gesamt</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>
                        {{ $item->description }}
                        @if (isset($item->metadata['settlement_type']))
                            <br><span class="muted">
                                {{ App\Enums\BillingSettlementType::from($item->metadata['settlement_type'])->label() }}
                                @if ($item->metadata['prorated'] ?? false)
                                    · zeitanteilig {{ number_format((float) $item->metadata['proration_factor'] * 100, 2, ',', '.') }} %
                                @endif
                            </span>
                        @endif
                    </td>
                    <td class="number">{{ number_format((float) $item->quantity, 4, ',', '.') }}</td>
                    <td class="number">{{ number_format((float) $item->unit_price, 4, ',', '.') }} €</td>
                    <td class="number">{{ number_format((float) $item->total_amount, 2, ',', '.') }} €</td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="3">Gesamtbetrag</td>
                <td class="number">{{ number_format((float) $invoice->total_amount, 2, ',', '.') }} €</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        {{ config('app.name', 'OKGV') }} · Die freie Verwaltungssoftware für Kleingartenvereine.
    </div>
</body>
</html>
