@extends('layouts.app')

@section('content')
<div class="app-shell">
    @include('partials.sidebar')

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-title">Editar usuario</h1>
                <p class="page-subtitle">{{ $user->name }} · {{ $user->email }}</p>
            </div>

            <a class="btn" style="background:#eef2f7;" href="{{ route('users.index') }}">Volver</a>
        </header>

        @include('users.partials.form', [
            'action' => route('users.update', $user),
            'method' => 'PUT',
            'user' => $user,
            'agencies' => $agencies,
            'roles' => $roles,
            'selectedRoles' => $selectedRoles,
            'buttonText' => 'Guardar cambios',
            'passwordRequired' => false,
        ])
    </main>
</div>
@endsection
