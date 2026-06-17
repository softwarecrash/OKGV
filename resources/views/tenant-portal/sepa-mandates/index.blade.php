@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Meine SEPA-Mandate</h1>
            <p class="text-secondary mb-0">
                Hier kannst du deine Lastschriftmandate einsehen, neu erteilen oder widerrufen.
            </p>
        </div>
        <a class="btn btn-primary" href="{{ route('tenant-portal.sepa-mandates.create') }}">Mandat hinterlegen</a>
    </div>

    <div class="alert alert-info">
        Bankdaten werden verschlüsselt gespeichert. Ein Widerruf stoppt zukünftige neue Lastschriften,
        bereits eingereichte Bankvorgänge können dadurch nicht rückwirkend zurückgenommen werden.
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Referenz</th>
                        <th>Konto</th>
                        <th>Gültigkeit</th>
                        <th>Status</th>
                        <th class="text-end">Aktion</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($mandates as $mandate)
                        <tr>
                            <td>{{ $mandate->mandate_reference }}</td>
                            <td>{{ $mandate->masked_iban }}</td>
                            <td>{{ $mandate->valid_from->format('d.m.Y') }} bis {{ $mandate->valid_until?->format('d.m.Y') ?? 'offen' }}</td>
                            <td>
                                {{ $mandate->status->label() }} · {{ $mandate->mandate_type->label() }}
                                @if ($mandate->revoked_at)
                                    <div class="small text-secondary">Widerrufen am {{ $mandate->revoked_at->format('d.m.Y H:i') }}</div>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($mandate->status === App\Enums\SepaMandateStatus::Active)
                                    <form method="POST"
                                          action="{{ route('tenant-portal.sepa-mandates.revoke', $mandate) }}"
                                          onsubmit="return confirm('SEPA-Mandat wirklich widerrufen?')">
                                        @csrf
                                        <input type="hidden" name="confirm_revoke" value="1">
                                        <div class="input-group input-group-sm">
                                            <input class="form-control" name="revocation_note" maxlength="1000" placeholder="Notiz optional">
                                            <button class="btn btn-outline-danger" type="submit">Widerrufen</button>
                                        </div>
                                    </form>
                                @else
                                    <span class="text-secondary">Keine Aktion</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <strong>Noch kein SEPA-Mandat.</strong><br>
                                <span class="text-secondary">Wenn du Lastschrift nutzen möchtest, hinterlege hier deine Bankdaten und Einwilligung.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $mandates->links() }}</div>
</div>
@endsection
