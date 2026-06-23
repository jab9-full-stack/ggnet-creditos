@extends('layouts.app')

@section('content')
<div class="app-shell">
    @include('partials.sidebar')

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-title">Nueva solicitud de crédito</h1>
                <p class="page-subtitle">Crea una solicitud en borrador asociada a un cliente existente.</p>
            </div>

            <a class="btn" style="background:#eef2f7;" href="{{ route('credit-requests.index') }}">Volver</a>
        </header>

        @include('credit-requests.partials.form', [
            'action' => route('credit-requests.store'),
            'method' => 'POST',
            'creditRequest' => $creditRequest,
            'clients' => $clients,
            'agencies' => $agencies,
            'buttonText' => 'Crear solicitud',
        ])
    </main>
</div>
@endsection
