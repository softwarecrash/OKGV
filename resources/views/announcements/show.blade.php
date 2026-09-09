@extends('layouts.app')

@section('content')
<div class="container">
    <a href="{{ route($publicView ? 'announcements.public.index' : 'announcements.index') }}">Zur Übersicht</a>
    <article class="card card-body border-0 shadow-sm my-3">
        <h1 class="h2">{{ $announcement->title }}</h1>
        <p class="text-secondary">{{ $announcement->starts_at->format('d.m.Y H:i') }} @if ($announcement->ends_at) bis {{ $announcement->ends_at->format('d.m.Y H:i') }} @endif</p>
        @if ($announcement->is_highlighted)<p><span class="badge text-bg-primary">Wichtige Mitteilung</span></p>@endif
        <div class="mb-3">{!! nl2br(e($announcement->body)) !!}</div>
        @if ($documents->isNotEmpty())
            <h2 class="h5">Dokumente</h2>
            <ul>
                @foreach ($documents as $document)
                    <li><a href="{{ route($publicView ? 'announcements.public.document' : 'announcements.document', [$announcement, $document]) }}">{{ $document->title }}</a></li>
                @endforeach
            </ul>
        @endif
        @unless ($publicView)
            @if ($read)
                <div class="alert alert-success mb-0">{{ $announcement->requires_confirmation ? 'Lesen bestätigt' : 'Als gelesen markiert' }} am {{ $read->read_at->format('d.m.Y H:i') }}.</div>
            @elsecan('acknowledge', $announcement)
                <form method="POST" action="{{ route('announcements.acknowledge', $announcement) }}">
                    @csrf
                    <p class="form-text">Mit diesem Klick bestätigst du deine Kenntnisnahme. Danach erlischt der Hinweis für diesen Beitrag.</p>
                    <button class="btn btn-primary" name="confirmation" value="1">{{ $announcement->requires_confirmation ? 'Lesen bestätigen' : 'Als gelesen markieren' }}</button>
                </form>
            @endif
        @endunless
    </article>
    @unless ($publicView)
        @can('create', App\Models\Announcement::class)
            <section class="card card-body border-0 shadow-sm mb-3">
                <h2 class="h5">Verwaltung · {{ $announcement->statusLabel() }}</h2>
                <p>Zielgruppe: {{ $announcement->audience->label() }} @if ($announcement->audience === App\Enums\AnnouncementAudience::Roles) · {{ collect($announcement->roles)->map(fn ($role) => App\Enums\UserRole::from($role)->label())->join(', ') }} @endif</p>
                @if ($announcement->audience === App\Enums\AnnouncementAudience::Public)<div class="alert alert-warning">Dieser Beitrag ist nach Veröffentlichung ohne Anmeldung im Internet lesbar. Prüfe den Text auf personenbezogene oder vertrauliche Angaben.</div>@endif
                <x-validation-errors />
                <div class="d-flex flex-wrap gap-2">
                    @can('update', $announcement)<a class="btn btn-outline-primary" href="{{ route('announcements.edit', $announcement) }}">Entwurf bearbeiten</a>@endcan
                    @can('publish', $announcement)
                        <form method="POST" action="{{ route('announcements.publish', $announcement) }}">@csrf<button class="btn btn-success" onclick="return confirm('Beitrag für die gewählte Zielgruppe verbindlich veröffentlichen?')">Veröffentlichen</button></form>
                    @endcan
                    @can('archive', $announcement)
                        <form method="POST" action="{{ route('announcements.archive', $announcement) }}">@csrf<button class="btn btn-outline-danger" onclick="return confirm('Beitrag zurückziehen? Er ist danach für Leser nicht mehr sichtbar.')">Zurückziehen</button></form>
                    @endcan
                </div>
            </section>
        @endcan
        @if ($reads !== null)
            <section class="card card-body border-0 shadow-sm">
                <h2 class="h5">{{ $announcement->requires_confirmation ? 'Lesebestätigungen' : 'Kenntnisnahmen' }} ({{ $reads->total() }})</h2>
                <p class="form-text">Erfasst werden nur ausdrückliche Bestätigungen angemeldeter Konten, keine Seitenaufrufe von Gästen.</p>
                @forelse ($reads as $receipt)
                    <p>{{ $receipt->user?->name ?? 'Nicht mehr zugeordnetes Konto' }} · {{ $receipt->read_at->format('d.m.Y H:i') }}</p>
                @empty <p class="text-secondary">Noch keine Bestätigungen vorhanden.</p> @endforelse
                {{ $reads->links() }}
            </section>
        @endif
    @endunless
</div>
@endsection
