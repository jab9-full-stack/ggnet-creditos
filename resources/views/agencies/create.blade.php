@extends('layouts.app')

@section('content')
<div class="app-shell">
    @include('partials.sidebar')

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-title">Nueva agencia</h1>
                <p class="page-subtitle">Registra una agencia operativa para usuarios y flujos futuros.</p>
            </div>

            <a class="btn" style="background:#eef2f7;" href="{{ route('agencies.index') }}">Volver</a>
        </header>

        @include('agencies.partials.form', [
            'action' => route('agencies.store'),
            'method' => 'POST',
            'agency' => $agency,
            'buttonText' => 'Crear agencia',
        ])
    </main>
</div>
@endsection
