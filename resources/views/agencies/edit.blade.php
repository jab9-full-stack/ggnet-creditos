@extends('layouts.app')

@section('content')
<div class="app-shell">
    @include('partials.sidebar')

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-title">Editar agencia</h1>
                <p class="page-subtitle">{{ $agency->code }} · {{ $agency->name }}</p>
            </div>

            <a class="btn" style="background:#eef2f7;" href="{{ route('agencies.index') }}">Volver</a>
        </header>

        @include('agencies.partials.form', [
            'action' => route('agencies.update', $agency),
            'method' => 'PUT',
            'agency' => $agency,
            'buttonText' => 'Guardar cambios',
        ])
    </main>
</div>
@endsection
