@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Serienmails</h1>
            <p class="text-secondary mb-0">Entwürfe, Versandstatus und historische Empfänger.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('mail-campaigns.create') }}">Serienmail erstellen</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Betreff</th><th>Empfängergruppe</th><th>Status</th><th>Ergebnis</th><th></th></tr></thead>
                <tbody>
                    @forelse ($campaigns as $campaign)
                        <tr>
                            <td><strong>{{ $campaign->subject }}</strong><br><small class="text-secondary">{{ $campaign->created_at->format('d.m.Y H:i') }}</small></td>
                            <td>{{ $campaign->recipient_group->label() }}</td>
                            <td>{{ $campaign->status->label() }}</td>
                            <td>{{ $campaign->sent_count }} versendet / {{ $campaign->failed_count }} fehlgeschlagen</td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('mail-campaigns.show', $campaign) }}">Details</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4"><strong>Noch keine Serienmail vorhanden.</strong><br><span class="text-secondary">Erstelle zuerst einen Entwurf und prüfe vor dem Versand die Empfängergruppe.</span></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $campaigns->links() }}</div>
</div>
@endsection
