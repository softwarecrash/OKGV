<x-validation-errors />

<div class="alert alert-info">
    Kategorien sind frei wählbar. So können neben Geräten und Werkzeugen auch Schlüssel, Anhänger oder andere Vereinsgegenstände erfasst werden.
</div>

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="inventory_number">Inventarnummer</label>
        <input class="form-control @error('inventory_number') is-invalid @enderror"
               id="inventory_number"
               name="inventory_number"
               value="{{ old('inventory_number', $item->inventory_number) }}"
               maxlength="100"
               required>
        <div class="form-text">Eindeutige vereinsinterne Kennung, zum Beispiel INV-001.</div>
        @error('inventory_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-5">
        <label class="form-label" for="name">Bezeichnung</label>
        <input class="form-control @error('name') is-invalid @enderror"
               id="name"
               name="name"
               value="{{ old('name', $item->name) }}"
               maxlength="255"
               required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label" for="category">Kategorie</label>
        <input class="form-control @error('category') is-invalid @enderror"
               id="category"
               name="category"
               value="{{ old('category', $item->category) }}"
               maxlength="255"
               list="inventory-category-suggestions">
        <datalist id="inventory-category-suggestions">
            <option value="Gerät">
            <option value="Werkzeug">
            <option value="Schlüssel">
            <option value="Anhänger">
            <option value="Pumpe">
        </datalist>
        <div class="form-text">Frei eingebbar; vorhandene Kategorien erscheinen später im Filter.</div>
        @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="status">Status</label>
        @if ($item->status === App\Enums\InventoryItemStatus::Issued)
            <input type="hidden" name="status" value="{{ App\Enums\InventoryItemStatus::Available->value }}">
            <input class="form-control" value="{{ $item->status->label() }}" disabled>
            <div class="form-text">Der Status wird bei der Rückgabe automatisch geändert.</div>
        @else
            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $item->status?->value ?? 'available') === $status->value)>
                        {{ $status->label() }}
                    </option>
                @endforeach
            </select>
        @endif
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="location">Standort</label>
        <input class="form-control @error('location') is-invalid @enderror"
               id="location"
               name="location"
               value="{{ old('location', $item->location) }}"
               maxlength="255">
        @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="serial_number">Seriennummer</label>
        <input class="form-control @error('serial_number') is-invalid @enderror"
               id="serial_number"
               name="serial_number"
               value="{{ old('serial_number', $item->serial_number) }}"
               maxlength="255">
        @error('serial_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="purchased_at">Anschaffungsdatum</label>
        <input class="form-control @error('purchased_at') is-invalid @enderror"
               id="purchased_at"
               name="purchased_at"
               type="date"
               value="{{ old('purchased_at', $item->purchased_at?->format('Y-m-d')) }}">
        @error('purchased_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="purchase_price">Anschaffungskosten</label>
        <div class="input-group">
            <input class="form-control @error('purchase_price') is-invalid @enderror"
                   id="purchase_price"
                   name="purchase_price"
                   inputmode="decimal"
                   value="{{ old('purchase_price', $item->purchase_price) }}"
                   placeholder="0,00">
            <span class="input-group-text">€</span>
            @error('purchase_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-text">Optional. Punkt und Komma werden als Dezimaltrennzeichen akzeptiert.</div>
    </div>
    <div class="col-12">
        <label class="form-label" for="description">Beschreibung</label>
        <textarea class="form-control @error('description') is-invalid @enderror"
                  id="description"
                  name="description"
                  rows="3"
                  maxlength="10000">{{ old('description', $item->description) }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label" for="notes">Interne Notizen</label>
        <textarea class="form-control @error('notes') is-invalid @enderror"
                  id="notes"
                  name="notes"
                  rows="3"
                  maxlength="10000">{{ old('notes', $item->notes) }}</textarea>
        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button class="btn btn-primary" type="submit">Speichern</button>
    <a class="btn btn-outline-secondary" href="{{ $item->exists ? route('inventory-items.show', $item) : route('inventory-items.index') }}">Abbrechen</a>
</div>
