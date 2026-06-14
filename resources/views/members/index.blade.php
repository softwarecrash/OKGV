@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h2 mb-0">Mitglieder</h1>
        @can('create', App\Models\Member::class)
            <a class="btn btn-primary" href="{{ route('members.create') }}">Mitglied anlegen</a>
        @endcan
    </div>

    <form class="card card-body border-0 shadow-sm mb-4" method="GET">
        <div class="row g-2">
            <div class="col-md-7">
                <label class="form-label" for="q">Suche</label>
                <input class="form-control" id="q" name="q" value="{{ request('q') }}" placeholder="Nummer, Name, E-Mail oder Ort">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Ohne Archiv</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-outline-primary w-100" type="submit">Filtern</button>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nummer</th>
                        <th>Name</th>
                        <th>Ort</th>
                        <th>Status</th>
                        <th class="text-end">Aktion</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($members as $member)
                        <tr>
                            <td>{{ $member->member_number }}</td>
                            <td>{{ $member->full_name }}</td>
                            <td>{{ $member->zip }} {{ $member->city }}</td>
                            <td>{{ $member->status->label() }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('members.show', $member) }}">Öffnen</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4">Keine Mitglieder gefunden.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $members->links() }}</div>
</div>
@endsection
