@extends('layouts.app')

@section('content')
<div class="app-shell">
    @include('partials.sidebar')

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-title">Editar documento</h1>
                <p class="page-subtitle">{{ $client->code }} · {{ $document->title }}</p>
            </div>

            <a class="btn" style="background:#eef2f7;" href="{{ route('clients.documents.index', $client) }}">Volver</a>
        </header>

        @include('client-documents.partials.form', [
            'action' => route('clients.documents.update', [$client, $document]),
            'method' => 'PUT',
            'client' => $client,
            'document' => $document,
            'types' => $types,
            'statuses' => $statuses,
            'buttonText' => 'Guardar cambios',
            'requiresFile' => false,
        ])
    </main>
</div>
@endsection
