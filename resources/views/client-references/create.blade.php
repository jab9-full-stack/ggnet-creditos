@extends('layouts.app')

@section('content')
<div class="app-shell">
    @include('partials.sidebar')

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-title">Nueva referencia</h1>
                <p class="page-subtitle">{{ $client->code }} · {{ $client->fullName() }}</p>
            </div>

            <a class="btn" style="background:#eef2f7;" href="{{ route('clients.references.index', $client) }}">Volver</a>
        </header>

        @include('client-references.partials.form', [
            'action' => route('clients.references.store', $client),
            'method' => 'POST',
            'client' => $client,
            'reference' => $reference,
            'types' => $types,
            'buttonText' => 'Crear referencia',
        ])
    </main>
</div>
@endsection
