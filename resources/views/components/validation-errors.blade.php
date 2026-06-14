@if ($errors->any())
    <div class="alert alert-danger" role="alert" aria-live="polite">
        <strong>Die Angaben konnten noch nicht gespeichert werden.</strong>
        <div>Bitte korrigiere die folgenden Felder. Deine bisherigen Eingaben bleiben erhalten.</div>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
