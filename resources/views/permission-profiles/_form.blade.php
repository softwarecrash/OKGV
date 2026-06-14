@csrf
@isset($profile) @method('PUT') @endisset

<div class="mb-3">
    <label class="form-label" for="name">Name der Vorlage</label>
    <input class="form-control @error('name') is-invalid @enderror"
           id="name" name="name" maxlength="100" required
           value="{{ old('name', $profile->name ?? '') }}">
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <div class="form-text">Ein verständlicher Zweckname, zum Beispiel „Vorstand Finanzen“ oder „Vorstand Stammdaten“.</div>
</div>

<div class="mb-4">
    <label class="form-label" for="description">Beschreibung</label>
    <textarea class="form-control @error('description') is-invalid @enderror"
              id="description" name="description" rows="3" maxlength="1000">{{ old('description', $profile->description ?? '') }}</textarea>
    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <div class="form-text">Erkläre kurz, für welche Aufgabe diese Vorlage vorgesehen ist.</div>
</div>

<fieldset>
    <legend class="h5">Enthaltene Rechte</legend>
    <div class="row g-3">
        @foreach ($permissions as $permission)
            <div class="col-lg-6">
                <div class="form-check border rounded p-3 ps-5 h-100">
                    <input class="form-check-input" type="checkbox"
                           id="permission-{{ $permission->value }}"
                           name="permissions[]" value="{{ $permission->value }}"
                           @checked(in_array($permission->value, old('permissions', $profile->permissions ?? []), true))>
                    <label class="form-check-label" for="permission-{{ $permission->value }}">
                        <strong>{{ $permission->label() }}</strong><br>
                        <span class="text-secondary">{{ $permission->description() }}</span>
                    </label>
                </div>
            </div>
        @endforeach
    </div>
</fieldset>

<input type="hidden" name="is_active" value="0">
<div class="form-check form-switch mt-4">
    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
           @checked(old('is_active', $profile->is_active ?? true))>
    <label class="form-check-label" for="is_active">Vorlage kann neuen Konten zugewiesen werden</label>
</div>

<button class="btn btn-primary mt-4">Vorlage speichern</button>
