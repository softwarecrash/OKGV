<x-validation-errors />

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="member_number">Mitgliedsnummer</label>
        <input class="form-control" id="member_number" name="member_number" value="{{ old('member_number', $member->member_number) }}" required maxlength="50">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="first_name">Vorname</label>
        <input class="form-control" id="first_name" name="first_name" value="{{ old('first_name', $member->first_name) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="last_name">Nachname</label>
        <input class="form-control" id="last_name" name="last_name" value="{{ old('last_name', $member->last_name) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="street">Straße und Hausnummer</label>
        <input class="form-control" id="street" name="street" value="{{ old('street', $member->street) }}" required>
    </div>
    <div class="col-md-2">
        <label class="form-label" for="zip">PLZ</label>
        <input class="form-control" id="zip" name="zip" value="{{ old('zip', $member->zip) }}" required maxlength="10">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="city">Ort</label>
        <input class="form-control" id="city" name="city" value="{{ old('city', $member->city) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="phone">Telefon</label>
        <input class="form-control" id="phone" name="phone" value="{{ old('phone', $member->phone) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="mobile">Mobil</label>
        <input class="form-control" id="mobile" name="mobile" value="{{ old('mobile', $member->mobile) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="email">E-Mail</label>
        <input class="form-control" type="email" id="email" name="email" value="{{ old('email', $member->email) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label" for="joined_at">Eintritt</label>
        <input class="form-control" type="date" id="joined_at" name="joined_at" value="{{ old('joined_at', $member->joined_at?->format('Y-m-d')) }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label" for="left_at">Austritt</label>
        <input class="form-control" type="date" id="left_at" name="left_at" value="{{ old('left_at', $member->left_at?->format('Y-m-d')) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label" for="status">Status</label>
        <select class="form-select" id="status" name="status" required>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $member->status?->value ?? 'active') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label" for="user_id">Pächterkonto</label>
        <select class="form-select" id="user_id" name="user_id">
            <option value="">Nicht verknüpft</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected((string) old('user_id', $member->user_id) === (string) $user->id)>{{ $user->name }} ({{ $user->email }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="form-label" for="notes">Interne Notizen</label>
        <textarea class="form-control" id="notes" name="notes" rows="4">{{ old('notes', $member->notes) }}</textarea>
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button class="btn btn-primary" type="submit">Speichern</button>
    <a class="btn btn-outline-secondary" href="{{ $member->exists ? route('members.show', $member) : route('members.index') }}">Abbrechen</a>
</div>
