@extends('layouts.app')

@section('content')
<div class="app-shell">
    @include('partials.sidebar')

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-title">Nuevo cliente</h1>
                <p class="page-subtitle">Registra el expediente base del cliente o solicitante.</p>
            </div>

            <a class="btn" style="background:#eef2f7;" href="{{ route('clients.index') }}">Volver</a>
        </header>

        @include('clients.partials.form', [
            'action' => route('clients.store'),
            'method' => 'POST',
            'client' => $client,
            'agencies' => $agencies,
            'buttonText' => 'Crear cliente',
        ])
    </main>
</div>
@endsection
