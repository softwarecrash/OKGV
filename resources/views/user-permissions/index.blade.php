@extends('layouts.app')

@section('content')
<div class="container">
    <div class="mb-4">
        <h1 class="h2 mb-1">Benutzerrechte</h1>
        <p class="text-secondary mb-0">
            Das Sonderrecht erlaubt revisionssichere Zählerstandkorrekturen.
            Originalwerte bleiben immer erhalten.
        </p>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Konto</th>
                        <th>Rolle</th>
                        <th>Zählerstände korrigieren</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <strong>{{ $user->name }}</strong><br>
                                <span class="text-secondary">{{ $user->email }}</span>
                            </td>
                            <td>{{ $user->role->label() }}</td>
                            <td>
                                <form class="d-flex align-items-center gap-3" method="POST" action="{{ route('user-permissions.update', $user) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="can_correct_meter_readings" value="0">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox"
                                               id="permission-{{ $user->id }}"
                                               name="can_correct_meter_readings" value="1"
                                               @checked($user->can_correct_meter_readings)>
                                        <label class="form-check-label" for="permission-{{ $user->id }}">
                                            {{ $user->can_correct_meter_readings ? 'Freigeschaltet' : 'Nicht freigeschaltet' }}
                                        </label>
                                    </div>
                                    <button class="btn btn-sm btn-primary">Speichern</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center py-4">Keine geeigneten Konten vorhanden.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
