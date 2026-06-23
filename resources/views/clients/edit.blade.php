@extends('layouts.app')

@section('content')
<div class="app-shell">
    @include('partials.sidebar')

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-title">Editar cliente</h1>
                <p class="page-subtitle">{{ $client->code }} · {{ $client->fullName() }}</p>
            </div>

            <a class="btn" style="background:#eef2f7;" href="{{ route('clients.index') }}">Volver</a>
        </header>

        @include('clients.partials.form', [
            'action' => route('clients.update', $client),
            'method' => 'PUT',
            'client' => $client,
            'agencies' => $agencies,
            'buttonText' => 'Guardar cambios',
        ])
    </main>
</div>
@endsection
