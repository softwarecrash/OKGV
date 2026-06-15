@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h2 mb-1">{{ $entry->full_name }}</h1>
            <span class="text-secondary">
                Eingang {{ $entry->applied_at->format('d.m.Y') }}
                · Priorität {{ $entry->priority }}
                · {{ $entry->status->label() }}
            </span>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('waiting-list-entries.index') }}">Zur Übersicht</a>
            @can('update', $entry)
                <a class="btn btn-primary" href="{{ route('waiting-list-entries.edit', $entry) }}">Bearbeiten</a>
            @endcan
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">Kontaktdaten</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">E-Mail</dt>
                        <dd class="col-sm-8"><a href="mailto:{{ $entry->email }}">{{ $entry->email }}</a></dd>
                        <dt class="col-sm-4">Telefon</dt>
                        <dd class="col-sm-8">{{ $entry->phone ?: '–' }}</dd>
                        <dt class="col-sm-4">Mobil</dt>
                        <dd class="col-sm-8">{{ $entry->mobile ?: '–' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">Bearbeitungsstand</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Eingangsdatum</dt>
                        <dd class="col-sm-7">{{ $entry->applied_at->format('d.m.Y') }}</dd>
                        <dt class="col-sm-5">Priorität</dt>
                        <dd class="col-sm-7">{{ $entry->priority }} von 5</dd>
                        <dt class="col-sm-5">Status</dt>
                        <dd class="col-sm-7">{{ $entry->status->label() }}</dd>
                    </dl>
                    <div class="form-text mt-2">Priorität 1 ist die höchste Priorität.</div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header">Interne Notizen</div>
                <div class="card-body">{!! nl2br(e($entry->notes ?: 'Keine Notizen.')) !!}</div>
            </div>
        </div>
    </div>
</div>
@endsection
