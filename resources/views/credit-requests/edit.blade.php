@extends('layouts.app')

@section('content')
<div class="app-shell">
    @include('partials.sidebar')

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-title">Editar solicitud</h1>
                <p class="page-subtitle">{{ $creditRequest->code }} · Solo editable mientras esté en borrador.</p>
            </div>

            <a class="btn" style="background:#eef2f7;" href="{{ route('credit-requests.show', $creditRequest) }}">Volver</a>
        </header>

        @include('credit-requests.partials.form', [
            'action' => route('credit-requests.update', $creditRequest),
            'method' => 'PUT',
            'creditRequest' => $creditRequest,
            'clients' => $clients,
            'agencies' => $agencies,
            'buttonText' => 'Guardar cambios',
        ])
    </main>
</div>
@endsection
