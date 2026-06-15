<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Mahnung {{ $notice->notice_number }}</title>
    <style>
        @page { margin: 22mm 20mm; }
        body { color: #263238; font-family: "DejaVu Sans", sans-serif; font-size: 11pt; line-height: 1.5; }
        h1 { color: #2E7D32; font-size: 20pt; margin: 14mm 0 8mm; }
        .details { background: #f5f7f5; margin: 8mm 0; padding: 5mm; }
        .amount { font-size: 14pt; font-weight: bold; }
        .cancelled { border: 2px solid #b02a37; color: #b02a37; font-weight: bold; padding: 4mm; }
        .footer { bottom: 8mm; color: #607d8b; font-size: 8pt; position: fixed; text-align: center; width: 100%; }
        .logo { max-height: 20mm; max-width: 60mm; margin-bottom: 3mm; }
    </style>
</head>
<body>
    @if ($association['logo_data_uri'])
        <img class="logo" src="{{ $association['logo_data_uri'] }}" alt="">
    @endif
    <div>{{ $association['name'] }}</div>
    @php($primaryRecipient = collect($notice->recipients)->firstWhere('is_primary', true) ?? collect($notice->recipients)->first())
    @if ($primaryRecipient)
        <p>
            @foreach ($notice->recipients as $recipient)
                {{ $recipient['first_name'] }} {{ $recipient['last_name'] }}@if (! $loop->last), @endif
            @endforeach
            <br>{{ $primaryRecipient['street'] }}<br>
            {{ $primaryRecipient['zip'] }} {{ $primaryRecipient['city'] }}
        </p>
    @endif
    <h1>{{ $notice->level }}. Mahnung</h1>
    @if ($notice->status === App\Enums\DunningNoticeStatus::Cancelled)
        <p class="cancelled">STORNIERT – dieses Dokument ist nur noch ein historischer Nachweis.</p>
    @endif
    <p>
        trotz Fälligkeit ist die folgende Rechnung weiterhin offen. Bitte begleiche die Gesamtforderung bis zur unten genannten Frist.
    </p>
    <div class="details">
        <strong>Mahnnummer:</strong> {{ $notice->notice_number }}<br>
        <strong>Rechnungsnummer:</strong> {{ $notice->invoice_number }}<br>
        <strong>Mahnstufe:</strong> {{ $notice->level }}<br>
        <strong>Neue Zahlungsfrist:</strong> {{ $notice->due_at->format('d.m.Y') }}<br><br>
        Rechnungsbetrag: {{ number_format((float) $notice->invoice_amount, 2, ',', '.') }} €<br>
        Frühere aktive Mahngebühren: {{ number_format((float) $notice->previous_fees_amount, 2, ',', '.') }} €<br>
        Mahngebühr dieser Stufe: {{ number_format((float) $notice->fee_amount, 2, ',', '.') }} €<br>
        <span class="amount">Gesamtforderung: {{ number_format((float) $notice->total_due, 2, ',', '.') }} €</span>
    </div>
    @if ($notice->note)
        <p>{!! nl2br(e($notice->note)) !!}</p>
    @endif
    <p>Falls die Zahlung inzwischen erfolgt ist, nimm bitte Kontakt mit dem Verein auf.</p>
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
            Mahnung {{ $notice->notice_number }} · erstellt am {{ $notice->issued_at->format('d.m.Y') }}
            @if ($association['email']) · {{ $association['email'] }}@endif
        @endif
    </div>
</body>
</html>
