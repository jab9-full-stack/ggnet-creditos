@extends('layouts.app')

@section('content')
<div class="app-shell">
    @include('partials.sidebar')

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-title">Editar referencia</h1>
                <p class="page-subtitle">{{ $client->code }} · {{ $reference->full_name }}</p>
            </div>

            <a class="btn" style="background:#eef2f7;" href="{{ route('clients.references.index', $client) }}">Volver</a>
        </header>

        @include('client-references.partials.form', [
            'action' => route('clients.references.update', [$client, $reference]),
            'method' => 'PUT',
            'client' => $client,
            'reference' => $reference,
            'types' => $types,
            'buttonText' => 'Guardar cambios',
        ])
    </main>
</div>
@endsection
