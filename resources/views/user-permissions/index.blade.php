@extends('layouts.app')

@section('content')
<div class="container">
    <div class="mb-4">
        <h1 class="h2 mb-1">Benutzerrechte</h1>
        <p class="text-secondary mb-0">
            Das Sonderrecht erlaubt revisionssichere Zählerstandkorrekturen.
            Originalwerte bleiben immer erhalten.
        </p>
        <div class="alert alert-warning mt-3 mb-0">
            Vergib dieses Recht nur an Personen, die gemeldete Werte fachlich prüfen dürfen. Jede Korrektur benötigt eine Begründung und wird mit Konto und Zeitpunkt protokolliert.
        </div>
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
                                <form class="d-flex align-items-center gap-3" method="POST" action="{{ route('user-permissions.update', $user) }}"
                                      onsubmit="return confirm('Korrekturrecht für {{ addslashes($user->name) }} wie ausgewählt speichern? Die Änderung betrifft zukünftige Zählerstandkorrekturen.')">
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
                        <tr><td colspan="3" class="text-center py-4"><strong>Keine geeigneten Konten vorhanden.</strong><br><span class="text-secondary">Das Sonderrecht kann nur Administrator- und Vorstandskonten zugewiesen werden.</span></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
