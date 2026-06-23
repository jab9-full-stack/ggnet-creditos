@extends('layouts.app')

@section('content')
<div class="app-shell">
    @include('partials.sidebar')

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-title">Nuevo documento</h1>
                <p class="page-subtitle">{{ $client->code }} · {{ $client->fullName() }}</p>
            </div>

            <a class="btn" style="background:#eef2f7;" href="{{ route('clients.documents.index', $client) }}">Volver</a>
        </header>

        @include('client-documents.partials.form', [
            'action' => route('clients.documents.store', $client),
            'method' => 'POST',
            'client' => $client,
            'document' => $document,
            'types' => $types,
            'statuses' => $statuses,
            'buttonText' => 'Cargar documento',
            'requiresFile' => true,
        ])
    </main>
</div>
@endsection
