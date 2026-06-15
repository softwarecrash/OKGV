@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-1">Rückgabe erfassen</h1>
    <p class="text-secondary mb-4">{{ $item->name }} · ausgegeben an {{ $loan->borrower_name }}</p>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <x-validation-errors />
            <div class="alert alert-info">
                Nach dem Speichern ist diese Ausgabe abgeschlossen. Wähle Wartung oder Verloren, wenn der Gegenstand nicht wieder regulär verfügbar ist.
            </div>
            <form method="POST" action="{{ route('inventory-items.loans.return.update', [$item, $loan]) }}">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="returned_at">Rückgabedatum</label>
                        <input class="form-control @error('returned_at') is-invalid @enderror"
                               id="returned_at"
                               name="returned_at"
                               type="date"
                               min="{{ $loan->issued_at->format('Y-m-d') }}"
                               value="{{ old('returned_at', today()->format('Y-m-d')) }}"
                               required>
                        @error('returned_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="return_status">Status nach Rückgabe</label>
                        <select class="form-select @error('return_status') is-invalid @enderror"
                                id="return_status"
                                name="return_status"
                                required>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(old('return_status', 'available') === $status->value)>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('return_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="condition_on_return">Zustand bei Rückgabe</label>
                        <textarea class="form-control @error('condition_on_return') is-invalid @enderror"
                                  id="condition_on_return"
                                  name="condition_on_return"
                                  rows="4"
                                  maxlength="10000">{{ old('condition_on_return') }}</textarea>
                        <div class="form-text">Beschädigungen oder fehlendes Zubehör hier nachvollziehbar festhalten.</div>
                        @error('condition_on_return')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-primary" type="submit">Rückgabe abschließen</button>
                    <a class="btn btn-outline-secondary" href="{{ route('inventory-items.show', $item) }}">Abbrechen</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
