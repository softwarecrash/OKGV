@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h2 mb-1">{{ $member->full_name }}</h1>
            <span class="text-secondary">{{ $member->member_number }} · {{ $member->status->label() }}</span>
        </div>
        <div class="d-flex gap-2">
            @can('update', $member)
                <a class="btn btn-primary" href="{{ route('members.edit', $member) }}">Bearbeiten</a>
            @endcan
            @can('archive', $member)
                @if ($member->status !== App\Enums\MemberStatus::Archived)
                    <form method="POST" action="{{ route('members.archive', $member) }}" onsubmit="return confirm('Mitglied wirklich archivieren?')">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-outline-danger" type="submit">Archivieren</button>
                    </form>
                @endif
            @endcan
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">Kontaktdaten</div>
                <div class="card-body">
                    <p>{{ $member->street }}<br>{{ $member->zip }} {{ $member->city }}</p>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Telefon</dt><dd class="col-sm-8">{{ $member->phone ?: '–' }}</dd>
                        <dt class="col-sm-4">Mobil</dt><dd class="col-sm-8">{{ $member->mobile ?: '–' }}</dd>
                        <dt class="col-sm-4">E-Mail</dt><dd class="col-sm-8">{{ $member->email ?: '–' }}</dd>
                        <dt class="col-sm-4">Eintritt</dt><dd class="col-sm-8">{{ $member->joined_at->format('d.m.Y') }}</dd>
                        <dt class="col-sm-4">Austritt</dt><dd class="col-sm-8">{{ $member->left_at?->format('d.m.Y') ?? '–' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">Parzellenhistorie</div>
                <div class="list-group list-group-flush">
                    @forelse ($member->parcelTenancies as $tenancy)
                        <a class="list-group-item list-group-item-action" href="{{ route('parcels.show', $tenancy->parcel) }}">
                            <strong>Parzelle {{ $tenancy->parcel->parcel_number }}</strong>
                            <span class="d-block text-secondary">
                                {{ $tenancy->starts_at->format('d.m.Y') }} bis {{ $tenancy->ends_at?->format('d.m.Y') ?? 'heute' }}
                                @if ($tenancy->is_primary) · Hauptpächter @endif
                            </span>
                        </a>
                    @empty
                        <div class="card-body">Keine Pächterzuordnung vorhanden.</div>
                    @endforelse
                </div>
            </div>
        </div>
        @if (auth()->user()->role->canViewAllMasterData())
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header">Interne Notizen</div>
                    <div class="card-body">{!! nl2br(e($member->notes ?: 'Keine Notizen.')) !!}</div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
