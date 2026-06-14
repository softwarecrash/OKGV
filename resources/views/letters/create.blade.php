@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-1">Brief erstellen</h1>
    <p class="text-secondary mb-4">Bei Auswahl eines Mitglieds wird dessen aktuelle Anschrift beim Speichern dauerhaft übernommen.</p>
    <form method="POST" action="{{ route('letters.store') }}" class="card border-0 shadow-sm">
        @csrf
        <div class="card-body">
            <div class="mb-4">
                <label class="form-label" for="member_id">Mitglied auswählen</label>
                <select class="form-select" id="member_id" name="member_id">
                    <option value="">Freie Anschrift verwenden</option>
                    @foreach ($members as $member)
                        <option value="{{ $member->id }}" @selected((int) old('member_id') === $member->id)>
                            {{ $member->member_number }} · {{ $member->full_name }} · {{ $member->zip }} {{ $member->city }}
                        </option>
                    @endforeach
                </select>
            </div>
            <fieldset class="border rounded p-3 mb-4">
                <legend class="h5 float-none w-auto px-2">Freie Anschrift</legend>
                <p class="text-secondary">Nur ausfüllen, wenn kein Mitglied ausgewählt wurde.</p>
                <div class="row g-3">
                    <div class="col-12"><label class="form-label" for="recipient_name">Name</label><input class="form-control" id="recipient_name" name="recipient_name" maxlength="255" value="{{ old('recipient_name') }}"></div>
                    <div class="col-12"><label class="form-label" for="street">Straße</label><input class="form-control" id="street" name="street" maxlength="255" value="{{ old('street') }}"></div>
                    <div class="col-md-4"><label class="form-label" for="zip">PLZ</label><input class="form-control" id="zip" name="zip" maxlength="20" value="{{ old('zip') }}"></div>
                    <div class="col-md-8"><label class="form-label" for="city">Ort</label><input class="form-control" id="city" name="city" maxlength="255" value="{{ old('city') }}"></div>
                </div>
            </fieldset>
            <div class="mb-3"><label class="form-label" for="subject">Betreff</label><input class="form-control" id="subject" name="subject" required maxlength="255" value="{{ old('subject') }}"></div>
            <div><label class="form-label" for="body">Brieftext</label><textarea class="form-control" id="body" name="body" rows="14" required maxlength="20000">{{ old('body') }}</textarea></div>
            <x-validation-errors />
        </div>
        <div class="card-footer bg-body border-0"><button class="btn btn-primary">Brief speichern</button></div>
    </form>
</div>
@endsection
