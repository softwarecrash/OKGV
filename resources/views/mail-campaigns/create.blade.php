@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-1">Serienmail erstellen</h1>
    <p class="text-secondary mb-4">Der Entwurf wird nicht sofort versendet. Die endgültigen Empfänger werden erst beim Versand als Snapshot gespeichert.</p>

    <form method="POST" action="{{ route('mail-campaigns.store') }}" class="card border-0 shadow-sm">
        @csrf
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label" for="recipient_group">Empfängergruppe</label>
                <select class="form-select" id="recipient_group" name="recipient_group" required>
                    @foreach ($groups as $group)
                        <option value="{{ $group->value }}" @selected(old('recipient_group') === $group->value)>
                            {{ $group->label() }} (aktuell {{ $recipientCounts[$group->value] }})
                        </option>
                    @endforeach
                </select>
                <div class="form-text">Doppelte Adressen werden zusammengeführt. Personen ohne gültige E-Mail-Adresse werden übersprungen.</div>
                <div class="mt-2">
                    @foreach ($groups as $group)
                        <div><strong>{{ $group->label() }}:</strong> {{ $group->description() }}</div>
                    @endforeach
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="subject">Betreff</label>
                <input class="form-control" id="subject" name="subject" required maxlength="255" value="{{ old('subject') }}">
            </div>
            <div>
                <label class="form-label" for="body">Nachricht</label>
                <textarea class="form-control" id="body" name="body" rows="12" required maxlength="20000">{{ old('body') }}</textarea>
                <div class="form-text">Die persönliche Anrede mit dem Namen des Empfängers wird automatisch ergänzt.</div>
            </div>
            <x-validation-errors />
        </div>
        <div class="card-footer bg-body border-0">
            <button class="btn btn-primary">Als Entwurf speichern</button>
        </div>
    </form>
</div>
@endsection
