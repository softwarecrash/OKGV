<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Zahlungserinnerung {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 22mm 20mm; }
        body { color: #263238; font-family: "DejaVu Sans", sans-serif; font-size: 11pt; line-height: 1.5; }
        h1 { color: #2E7D32; font-size: 20pt; margin: 14mm 0 8mm; }
        .details { background: #f5f7f5; margin: 8mm 0; padding: 5mm; }
        .amount { font-size: 14pt; font-weight: bold; }
        .footer { bottom: 8mm; color: #607d8b; font-size: 8pt; position: fixed; text-align: center; width: 100%; }
    </style>
</head>
<body>
    <div>{{ config('app.name', 'OKGV') }}</div>
    <p>
        @foreach ($invoice->recipients as $recipient)
            {{ $recipient->full_name }}@if (! $loop->last), @endif
        @endforeach
    </p>
    <h1>Zahlungserinnerung</h1>
    <p>
        bei unserer Prüfung ist uns aufgefallen, dass die folgende Rechnung noch offen ist.
        Falls die Zahlung inzwischen erfolgt ist, betrachte dieses Schreiben bitte als gegenstandslos.
    </p>
    <div class="details">
        <strong>Rechnungsnummer:</strong> {{ $invoice->invoice_number }}<br>
        <strong>Rechnungsdatum:</strong> {{ $invoice->issued_at->format('d.m.Y') }}<br>
        <strong>Ursprünglich fällig:</strong> {{ $invoice->due_at->format('d.m.Y') }}<br>
        <span class="amount">Offener Betrag: {{ number_format((float) $invoice->total_amount, 2, ',', '.') }} €</span>
    </div>
    <p>Bitte prüfe den Zahlungsvorgang und überweise den offenen Betrag zeitnah.</p>
    <p>Mit freundlichen Grüßen<br>{{ config('app.name', 'OKGV') }}</p>
    <div class="footer">Zahlungserinnerung ohne Mahnstufe oder Mahngebühr</div>
</body>
</html>
