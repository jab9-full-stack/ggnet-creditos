@extends('layouts.app')

@section('content')
<div class="app-shell">
    @include('partials.sidebar')

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-title">Nuevo usuario</h1>
                <p class="page-subtitle">Crea un acceso administrativo con agencia y roles.</p>
            </div>

            <a class="btn" style="background:#eef2f7;" href="{{ route('users.index') }}">Volver</a>
        </header>

        @include('users.partials.form', [
            'action' => route('users.store'),
            'method' => 'POST',
            'user' => $user,
            'agencies' => $agencies,
            'roles' => $roles,
            'selectedRoles' => $selectedRoles,
            'buttonText' => 'Crear usuario',
            'passwordRequired' => true,
        ])
    </main>
</div>
@endsection
