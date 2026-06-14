@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Bitte prüfe die Eingaben.</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
