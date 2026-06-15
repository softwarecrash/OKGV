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
        .logo { max-height: 20mm; max-width: 60mm; margin-bottom: 3mm; }
    </style>
</head>
<body>
    @if ($association['logo_data_uri'])
        <img class="logo" src="{{ $association['logo_data_uri'] }}" alt="">
    @endif
    <div>{{ $association['name'] }}</div>
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
    @if ($association['bank_iban'])
        <p>
            <strong>Bankverbindung:</strong>
            {{ $association['bank_iban'] }}
            @if ($association['bank_bic']) · {{ $association['bank_bic'] }}@endif
        </p>
    @endif
    <p>Mit freundlichen Grüßen<br>{{ $association['name'] }}</p>
    <div class="footer">
        @if ($association['document_footer'])
            {!! nl2br(e($association['document_footer'])) !!}
        @else
            Zahlungserinnerung ohne Mahnstufe oder Mahngebühr
            @if ($association['email']) · {{ $association['email'] }}@endif
        @endif
    </div>
</body>
</html>
