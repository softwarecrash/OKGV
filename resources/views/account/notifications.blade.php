@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header"><h1 class="h4 mb-0">E-Mail-Benachrichtigungen</h1></div>
                <div class="card-body">
                    <p class="text-secondary">Wähle, worüber wir dich per E-Mail informieren dürfen. Alle passenden Benachrichtigungen sind zunächst aktiviert. Sicherheitsmails, etwa zur Kontobestätigung oder Passwortzurücksetzung, bleiben davon unberührt.</p>
                    <form method="POST" action="{{ route('account.notifications.update') }}">
                        @csrf
                        @method('PUT')
                        @foreach ($topics as $topic)
                            <input type="hidden" name="preferences[{{ $topic->value }}]" value="0">
                            <div class="form-check form-switch border rounded p-3 ps-5 mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="topic-{{ $topic->value }}" name="preferences[{{ $topic->value }}]" value="1" @checked($user->wantsEmailNotification($topic))>
                                <label class="form-check-label" for="topic-{{ $topic->value }}"><strong>{{ $topic->label() }}</strong><br><span class="text-secondary">{{ $topic->description() }}</span></label>
                            </div>
                        @endforeach
                        <button class="btn btn-primary">Benachrichtigungen speichern</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
