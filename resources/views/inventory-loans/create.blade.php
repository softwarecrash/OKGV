@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-1">{{ $item->name }} ausgeben</h1>
    <p class="text-secondary mb-4">Inventarnummer {{ $item->inventory_number }}</p>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <x-validation-errors />
            <div class="alert alert-info">
                Wähle optional ein Mitglied aus. Der Empfängername wird zusätzlich gespeichert, damit die Historie bei späteren Stammdatenänderungen verständlich bleibt.
            </div>
            <form method="POST" action="{{ route('inventory-items.loans.store', $item) }}"
                  x-data="{ memberNames: @js($members->mapWithKeys(fn ($member) => [(string) $member->id => $member->full_name])), selected: '{{ old('member_id') }}', borrower: @js(old('borrower_name', '')) }"
                  x-init="if (selected && !borrower) borrower = memberNames[selected] ?? ''">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="member_id">Mitglied auswählen</label>
                        <select class="form-select @error('member_id') is-invalid @enderror"
                                id="member_id"
                                name="member_id"
                                x-model="selected"
                                @change="if (selected) borrower = memberNames[selected] ?? borrower">
                            <option value="">Kein Mitglied / andere Person</option>
                            @foreach ($members as $member)
                                <option value="{{ $member->id }}">{{ $member->last_name }}, {{ $member->first_name }} ({{ $member->member_number }})</option>
                            @endforeach
                        </select>
                        @error('member_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="borrower_name">Empfänger</label>
                        <input class="form-control @error('borrower_name') is-invalid @enderror"
                               id="borrower_name"
                               name="borrower_name"
                               x-model="borrower"
                               maxlength="255"
                               required>
                        <div class="form-text">Kann auch ein externer Name oder eine Funktion sein.</div>
                        @error('borrower_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="issued_at">Ausgabedatum</label>
                        <input class="form-control @error('issued_at') is-invalid @enderror"
                               id="issued_at"
                               name="issued_at"
                               type="date"
                               value="{{ old('issued_at', today()->format('Y-m-d')) }}"
                               required>
                        @error('issued_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="due_at">Rückgabefrist</label>
                        <input class="form-control @error('due_at') is-invalid @enderror"
                               id="due_at"
                               name="due_at"
                               type="date"
                               value="{{ old('due_at') }}">
                        <div class="form-text">Optional. Nach Ablauf erscheint ein Aktionshinweis.</div>
                        @error('due_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="condition_on_issue">Zustand bei Ausgabe</label>
                        <textarea class="form-control @error('condition_on_issue') is-invalid @enderror"
                                  id="condition_on_issue"
                                  name="condition_on_issue"
                                  rows="3"
                                  maxlength="10000">{{ old('condition_on_issue') }}</textarea>
                        @error('condition_on_issue')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="notes">Notizen</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror"
                                  id="notes"
                                  name="notes"
                                  rows="3"
                                  maxlength="10000">{{ old('notes') }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-primary" type="submit">Ausgabe speichern</button>
                    <a class="btn btn-outline-secondary" href="{{ route('inventory-items.show', $item) }}">Abbrechen</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
