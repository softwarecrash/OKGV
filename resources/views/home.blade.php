@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">Dashboard</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <h1 class="h4">Willkommen bei OKGV</h1>
                    <p class="mb-0">Die technische Projektbasis ist eingerichtet. Fachmodule werden phasenweise ergänzt.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
