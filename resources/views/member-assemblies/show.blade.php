@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2">{{ $assembly->title }}</h1>
    <x-validation-errors />
    <p>{{ $assembly->mode->label() }} · {{ $assembly->scheduled_at->format('d.m.Y H:i') }} · {{ $assembly->statusLabel() }}</p>
    <p><strong>Ort/Zugang:</strong> {{ $assembly->location }}</p>
    @if ($assembly->electronic_rights_notice)
        <div class="alert alert-info"><strong>Elektronische Rechte:</strong><br>{!! nl2br(e($assembly->electronic_rights_notice)) !!}</div>
    @endif
    <h2 class="h5">Tagesordnung</h2><div class="mb-4">{!! nl2br(e($assembly->agenda)) !!}</div>
    @can('attend', $assembly)
        @unless ($participation)
            <form method="POST" action="{{ route('member-assemblies.attend', $assembly) }}" class="mb-4">@csrf<button class="btn btn-primary">Elektronische Teilnahme bestätigen</button></form>
        @endunless
    @endcan
    <h2 class="h4">Beschlüsse</h2>
    @foreach ($assembly->resolutions as $resolution)
        <section class="card card-body mb-3"><h3 class="h5">{{ $resolution->title }}</h3><p>{!! nl2br(e($resolution->body)) !!}</p>
        @can('vote', $assembly)
            @if ($participation && ! $votes[$resolution->id])
                <form method="POST" action="{{ route('member-assemblies.vote', $assembly) }}">@csrf<input type="hidden" name="resolution_id" value="{{ $resolution->id }}"><select class="form-select mb-2" name="choice">@foreach (App\Enums\MemberAssemblyVoteChoice::cases() as $choice)<option value="{{ $choice->value }}">{{ $choice->label() }}</option>@endforeach</select><div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="confirmed" value="1" required id="vote-{{ $resolution->id }}"><label class="form-check-label" for="vote-{{ $resolution->id }}">Meine Stimme ist nicht geheim und wird verbindlich protokolliert.</label></div><button class="btn btn-outline-primary">Stimme abgeben</button></form>
            @elseif ($votes[$resolution->id])
                <span class="text-success">Deine Stimme ist protokolliert.</span>
            @endif
        @endcan
        @if ($assembly->final_minutes_snapshot)<p class="mt-2"><strong>Ergebnis:</strong> Ja {{ $assembly->final_minutes_snapshot['resolutions'][$loop->index]['yes'] }}, Nein {{ $assembly->final_minutes_snapshot['resolutions'][$loop->index]['no'] }}, Enthaltungen {{ $assembly->final_minutes_snapshot['resolutions'][$loop->index]['abstain'] }}</p>@endif
        </section>
    @endforeach
    @can('publish', $assembly)<form method="POST" action="{{ route('member-assemblies.publish', $assembly) }}">@csrf<input type="checkbox" name="confirmed" value="1" required> Ich bestätige die Prüfung von Satzung, Fristen und Beschlussgegenständen. <button class="btn btn-outline-primary">Einladung veröffentlichen</button></form>@endcan
    @can('finalize', $assembly)<form method="POST" action="{{ route('member-assemblies.finalize', $assembly) }}" class="mt-3">@csrf<input type="checkbox" name="confirmed" value="1" required> Ich bestätige den endgültigen Abschluss. <button class="btn btn-outline-primary">Versammlung abschließen</button></form>@endcan
</div>
@endsection
