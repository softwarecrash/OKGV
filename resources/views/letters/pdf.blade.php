<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>{{ $letter->subject }}</title>
    <style>
        @page { margin: 22mm 20mm; }
        body { color: #263238; font-family: "DejaVu Sans", sans-serif; font-size: 11pt; line-height: 1.5; }
        h1 { color: #2E7D32; font-size: 18pt; margin: 15mm 0 8mm; }
        .sender { color: #607d8b; font-size: 9pt; }
        .logo { max-height: 20mm; max-width: 60mm; margin-bottom: 3mm; }
        .address { margin-top: 12mm; }
        .footer { bottom: 8mm; color: #607d8b; font-size: 8pt; position: fixed; text-align: center; width: 100%; }
    </style>
</head>
<body>
    @if ($association['logo_data_uri'])
        <img class="logo" src="{{ $association['logo_data_uri'] }}" alt="">
    @endif
    <div class="sender">
        {{ $association['name'] }}
        @if ($association['street']) · {{ $association['street'] }} · {{ $association['zip'] }} {{ $association['city'] }}@endif
    </div>
    <div class="address">
        {{ $letter->recipient_name }}<br>
        {{ $letter->street }}<br>
        {{ $letter->zip }} {{ $letter->city }}
    </div>
    <h1>{{ $letter->subject }}</h1>
    <div>{!! nl2br(e($letter->body)) !!}</div>
    <div class="footer">
        @if ($association['document_footer'])
            {!! nl2br(e($association['document_footer'])) !!}
        @else
            {{ $association['name'] }}
            @if ($association['email']) · {{ $association['email'] }}@endif
            @if ($association['phone']) · {{ $association['phone'] }}@endif
        @endif
    </div>
</body>
</html>
