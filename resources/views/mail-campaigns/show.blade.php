@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">{{ $campaign->subject }}</h1>
            <p class="text-secondary mb-0">{{ $campaign->recipient_group->label() }} · {{ $campaign->status->label() }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('mail-campaigns.index') }}">Zur Übersicht</a>
    </div>

    @if ($campaign->status === App\Enums\MailCampaignStatus::Draft)
        <div class="alert alert-warning">
            Beim jetzigen Versand würden {{ $prospectiveCount }} eindeutige gültige Adresse(n) angeschrieben.
            Nach dem Versand kann dieser Entwurf nicht erneut verwendet werden.
        </div>
        @can('send', $campaign)
            <form method="POST" action="{{ route('mail-campaigns.send', $campaign) }}" class="mb-4"
                  onsubmit="return confirm('Serienmail jetzt an {{ $prospectiveCount }} Adresse(n) senden? Dieser Vorgang kann nicht rückgängig gemacht werden.')">
                @csrf
                <button class="btn btn-primary">Jetzt verbindlich versenden</button>
            </form>
        @endcan
    @elseif ($campaign->status === App\Enums\MailCampaignStatus::Sending)
        <div class="alert alert-info">
            Der Versand läuft im Hintergrund. Aktualisiere diese Seite, um den aktuellen Stand zu sehen.
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5">Nachricht</h2>
            <div>{!! nl2br(e($campaign->body)) !!}</div>
        </div>
    </div>

    @if ($campaign->recipients->isNotEmpty())
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Empfänger</th><th>E-Mail</th><th>Status</th><th>Hinweis</th></tr></thead>
                    <tbody>
                        @foreach ($campaign->recipients as $recipient)
                            <tr>
                                <td>{{ $recipient->name }}</td>
                                <td>{{ $recipient->email }}</td>
                                <td>{{ $recipient->status->label() }}</td>
                                <td>{{ $recipient->error_message ?: ($recipient->sent_at?->format('d.m.Y H:i') ?? '–') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
