<!doctype html>
<html lang="de">
<body style="color:#263238;font-family:Arial,sans-serif;line-height:1.5">
    <p>Hallo {{ $recipient->name }},</p>
    <div>{!! nl2br(e($campaign->body)) !!}</div>
    <div style="color:#607d8b;margin-top:24px">
        @if ($association['email_signature'])
            {!! nl2br(e($association['email_signature'])) !!}
        @else
            {{ $association['name'] }}
            @if ($association['email'])<br>{{ $association['email'] }}@endif
        @endif
    </div>
</body>
</html>
