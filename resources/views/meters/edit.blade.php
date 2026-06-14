@extends('layouts.app')
@section('content')
<div class="container"><h1 class="h2 mb-4">Zähler bearbeiten</h1>
<form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('meters.update', $meter) }}">@csrf @method('PUT')
<x-validation-errors />
<div class="row g-3"><div class="col-md-6"><label class="form-label" for="meter_number">Zählernummer</label><input class="form-control" id="meter_number" name="meter_number" value="{{ old('meter_number', $meter->meter_number) }}" required></div>
@if(in_array($meter->status, [App\Enums\MeterStatus::Active, App\Enums\MeterStatus::Defective], true))
<div class="col-md-6"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="active" @selected(old('status', $meter->status->value) === 'active')>Aktiv</option><option value="defective" @selected(old('status', $meter->status->value) === 'defective')>Defekt</option></select></div>
@endif
<div class="col-12"><label class="form-label" for="notes">Interne Notizen</label><textarea class="form-control" id="notes" name="notes">{{ old('notes', $meter->notes) }}</textarea></div></div>
<div class="mt-4"><button class="btn btn-primary">Speichern</button></div></form></div>
@endsection
