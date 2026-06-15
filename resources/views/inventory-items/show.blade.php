@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h2 mb-1">{{ $item->name }}</h1>
            <span class="text-secondary">{{ $item->inventory_number }} · {{ $item->status->label() }}</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('inventory-items.index') }}">Zur Übersicht</a>
            @can('update', $item)
                <a class="btn btn-outline-primary" href="{{ route('inventory-items.edit', $item) }}">Bearbeiten</a>
            @endcan
            @if ($item->status === App\Enums\InventoryItemStatus::Available)
                @can('issue', $item)
                    <a class="btn btn-primary" href="{{ route('inventory-items.loans.create', $item) }}">Ausgeben</a>
                @endcan
            @elseif ($openLoan)
                @can('return', $item)
                    <a class="btn btn-primary" href="{{ route('inventory-items.loans.return.edit', [$item, $openLoan]) }}">Rückgabe erfassen</a>
                @endcan
            @endif
        </div>
    </div>

    @if ($openLoan?->is_overdue)
        <div class="alert alert-warning">
            Die Rückgabe war für den {{ $openLoan->due_at->format('d.m.Y') }} vorgesehen und ist überfällig.
        </div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">Stammdaten</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Inventarnummer</dt>
                        <dd class="col-sm-7">{{ $item->inventory_number }}</dd>
                        <dt class="col-sm-5">Kategorie</dt>
                        <dd class="col-sm-7">{{ $item->category ?: '–' }}</dd>
                        <dt class="col-sm-5">Status</dt>
                        <dd class="col-sm-7">{{ $item->status->label() }}</dd>
                        <dt class="col-sm-5">Standort</dt>
                        <dd class="col-sm-7">{{ $item->location ?: '–' }}</dd>
                        <dt class="col-sm-5">Seriennummer</dt>
                        <dd class="col-sm-7">{{ $item->serial_number ?: '–' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header">Anschaffung</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Datum</dt>
                        <dd class="col-sm-7">{{ $item->purchased_at?->format('d.m.Y') ?? '–' }}</dd>
                        <dt class="col-sm-5">Kosten</dt>
                        <dd class="col-sm-7">{{ $item->purchase_price !== null ? number_format((float) $item->purchase_price, 2, ',', '.') . ' €' : '–' }}</dd>
                    </dl>
                    @if ($item->description)
                        <hr>
                        <div>{!! nl2br(e($item->description)) !!}</div>
                    @endif
                </div>
            </div>
        </div>
        @if ($item->notes)
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header">Interne Notizen</div>
                    <div class="card-body">{!! nl2br(e($item->notes)) !!}</div>
                </div>
            </div>
        @endif
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header">Ausgabehistorie</div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Empfänger</th>
                        <th>Ausgabe</th>
                        <th>Frist</th>
                        <th>Rückgabe</th>
                        <th>Bearbeitung</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($item->loans as $loan)
                        <tr>
                            <td>
                                {{ $loan->borrower_name }}
                                @if ($loan->member)
                                    <div class="small text-secondary">Mitglied {{ $loan->member->member_number }}</div>
                                @endif
                            </td>
                            <td>
                                {{ $loan->issued_at->format('d.m.Y') }}
                                <div class="small text-secondary">{{ $loan->issuer?->name ?: 'System' }}</div>
                            </td>
                            <td class="{{ $loan->is_overdue ? 'text-danger fw-semibold' : '' }}">
                                {{ $loan->due_at?->format('d.m.Y') ?? '–' }}
                            </td>
                            <td>
                                {{ $loan->returned_at?->format('d.m.Y') ?? 'Offen' }}
                                @if ($loan->receiver)
                                    <div class="small text-secondary">{{ $loan->receiver->name }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($loan->condition_on_issue)
                                    <div><strong>Ausgabe:</strong> {{ $loan->condition_on_issue }}</div>
                                @endif
                                @if ($loan->condition_on_return)
                                    <div><strong>Rückgabe:</strong> {{ $loan->condition_on_return }}</div>
                                @endif
                                @if ($loan->notes)
                                    <div class="small text-secondary">{{ $loan->notes }}</div>
                                @endif
                                @if (! $loan->condition_on_issue && ! $loan->condition_on_return && ! $loan->notes)
                                    –
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                Noch keine Ausgabe erfasst. Verfügbare Gegenstände können oben ausgegeben werden.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
