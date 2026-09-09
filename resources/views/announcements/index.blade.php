@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2">{{ $publicView ? 'Öffentliche Bekanntmachungen' : 'Schwarzes Brett' }}</h1>
    <p class="text-secondary">{{ $manage ? 'Hier findest du auch Entwürfe, geplante und zurückgezogene Beiträge.' : 'Neuigkeiten und wichtige Informationen aus dem Verein.' }}</p>
    @unless ($publicView)
        <div class="d-flex flex-wrap gap-2 mb-4">
            <a class="btn btn-outline-primary" href="{{ route('announcements.index') }}">Aktuelle Beiträge</a>
            <a class="btn btn-outline-primary" href="{{ route('announcements.index', ['unread' => 1]) }}">Noch nicht gelesen</a>
            @can('create', App\Models\Announcement::class)
                <a class="btn btn-primary" href="{{ route('announcements.create') }}">Beitrag anlegen</a>
                <a class="btn btn-outline-secondary" href="{{ route('announcements.index', ['manage' => 1]) }}">Beiträge verwalten</a>
            @endcan
        </div>
    @endunless
    @forelse ($announcements as $announcement)
        <article class="card shadow-sm mb-3 {{ $announcement->is_highlighted ? 'border-primary' : 'border-0' }}">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 mb-2">
                    @if ($announcement->is_highlighted)<span class="badge text-bg-primary">Wichtig</span>@endif
                    @if ($announcement->requires_confirmation)<span class="badge text-bg-warning">Lesebestätigung erbeten</span>@endif
                    @if (! $publicView && ! $manage && ! $announcement->has_read)<span class="badge text-bg-info">Ungelesen</span>@endif
                    @if ($manage)<span class="badge text-bg-secondary">{{ $announcement->statusLabel() }}</span>@endif
                </div>
                <h2 class="h5"><a href="{{ route($publicView ? 'announcements.public.show' : 'announcements.show', $announcement) }}">{{ $announcement->title }}</a></h2>
                <p class="small text-secondary">{{ $announcement->starts_at->format('d.m.Y H:i') }} @if ($announcement->ends_at) bis {{ $announcement->ends_at->format('d.m.Y H:i') }} @endif</p>
                <p class="mb-0">{{ \Illuminate\Support\Str::limit($announcement->body, 220) }}</p>
            </div>
        </article>
    @empty
        <div class="alert alert-info">Hier gibt es derzeit keine passenden Beiträge.</div>
    @endforelse
    {{ $announcements->links() }}
</div>
@endsection
