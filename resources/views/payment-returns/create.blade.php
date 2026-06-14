@extends('layouts.app')
@section('content')
<div class="container">
    <h1 class="h2 mb-4">Rücklastschrift erfassen</h1>
    <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('payment-returns.store', $item) }}">
        @csrf
        <x-validation-errors />
        <div class="alert alert-warning">Die Rechnung {{ $item->invoice->invoice_number }} wird wieder als offen gekennzeichnet. Der ursprüngliche Lastschriftposten und der Rückgabegrund bleiben dauerhaft erhalten.</div>
        <p><strong>{{ $item->invoice->member->full_name }}</strong><br>{{ number_format((float) $item->amount, 2, ',', '.') }} €</p>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label" for="return_reason_code">ISO-Grundcode</label><input class="form-control text-uppercase" id="return_reason_code" name="return_reason_code" maxlength="4" required placeholder="AC01"><div class="form-text">Vierstelliger Code aus der Bankrückmeldung, zum Beispiel AC01 für eine fehlerhafte IBAN.</div></div>
            <div class="col-md-4"><label class="form-label" for="returned_at">Rückgabedatum</label><input class="form-control" type="date" id="returned_at" name="returned_at" value="{{ now()->format('Y-m-d') }}" required></div>
            <div class="col-12"><label class="form-label" for="return_reason_text">Erläuterung</label><input class="form-control" id="return_reason_text" name="return_reason_text" maxlength="255" placeholder="Optionaler verständlicher Hinweis"></div>
        </div>
        <div class="d-flex gap-2 mt-4"><button class="btn btn-danger">Rücklastschrift speichern</button><a class="btn btn-outline-secondary" href="{{ route('payment-batches.show', $item->payment_batch_id) }}">Abbrechen</a></div>
    </form>
</div>
@endsection
