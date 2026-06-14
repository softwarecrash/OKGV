@extends('layouts.app')
@section('content')
<div class="container"><h1 class="h2 mb-4">Zähler {{ $meter->meter_number }} wechseln</h1><form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('meters.replace.store', $meter) }}">@csrf
<x-validation-errors /><div class="row g-3">
<div class="col-md-6"><label class="form-label" for="replaced_at">Wechseldatum</label><input class="form-control" type="date" id="replaced_at" name="replaced_at" value="{{ old('replaced_at', now()->format('Y-m-d')) }}" required></div>
<div class="col-md-6"><label class="form-label" for="end_reading">Endstand alter Zähler</label><input class="form-control" type="number" min="0" step="0.0001" id="end_reading" name="end_reading" value="{{ old('end_reading') }}" required></div>
<div class="col-md-6"><label class="form-label" for="meter_number">Nummer neuer Zähler</label><input class="form-control" id="meter_number" name="meter_number" value="{{ old('meter_number') }}" required></div>
<div class="col-md-6"><label class="form-label" for="start_reading">Startstand neuer Zähler</label><input class="form-control" type="number" min="0" step="0.0001" id="start_reading" name="start_reading" value="{{ old('start_reading', '0') }}" required></div>
<div class="col-12"><label class="form-label" for="notes">Notizen zum neuen Zähler</label><textarea class="form-control" id="notes" name="notes">{{ old('notes') }}</textarea></div></div><div class="mt-4"><button class="btn btn-danger">Zählerwechsel speichern</button></div></form></div>
@endsection
