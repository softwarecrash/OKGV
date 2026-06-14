<!doctype html>
<html lang="de">
<body style="color:#263238;font-family:Arial,sans-serif;line-height:1.5">
    <p>Hallo {{ $recipient->name }},</p>
    <div>{!! nl2br(e($campaign->body)) !!}</div>
    <p style="color:#607d8b">
        {{ config('app.name', 'OKGV') }}
    </p>
</body>
</html>
