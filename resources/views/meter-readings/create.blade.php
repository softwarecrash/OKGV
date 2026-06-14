@extends('layouts.app')
@section('content')
<div class="container"><h1 class="h2 mb-4">Zählerstand erfassen</h1><form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('meter-readings.store') }}">@csrf
<x-validation-errors /><div class="row g-3">
<div class="col-md-6"><label class="form-label" for="meter_id">Zähler</label><select class="form-select" id="meter_id" name="meter_id">@foreach($meters as $meter)<option value="{{ $meter->id }}" @selected((string) old('meter_id', $reading->meter_id) === (string) $meter->id)>{{ $meter->meter_number }} · Parzelle {{ $meter->parcel->parcel_number }}</option>@endforeach</select></div>
<div class="col-md-3"><label class="form-label" for="reading_date">Datum</label><input class="form-control" type="date" id="reading_date" name="reading_date" value="{{ old('reading_date', $reading->reading_date?->format('Y-m-d')) }}" required></div>
<div class="col-md-3"><label class="form-label" for="reading_value">Stand</label><input class="form-control" type="number" min="0" step="0.0001" id="reading_value" name="reading_value" value="{{ old('reading_value') }}" required></div>
<div class="col-md-4"><label class="form-label" for="source">Quelle</label><select class="form-select" id="source" name="source">@foreach($sources as $source)<option value="{{ $source->value }}" @selected(old('source', $reading->source?->value) === $source->value)>{{ $source->label() }}</option>@endforeach</select></div>
<div class="col-12"><label class="form-label" for="notes">Interne Notizen</label><textarea class="form-control" id="notes" name="notes">{{ old('notes') }}</textarea></div></div><div class="mt-4"><button class="btn btn-primary">Speichern</button></div></form></div>
@endsection
