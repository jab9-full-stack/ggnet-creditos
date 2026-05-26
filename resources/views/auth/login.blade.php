@extends('layouts.app')

@section('content')
<div class="auth-shell">
    <section class="auth-brand">
        <div class="brand-pill">
            <span>BIENESTAR</span>
            <span style="opacity:.65;">GGNET Créditos</span>
        </div>

        <div>
            <h1>Gestión financiera clara, segura y controlada.</h1>
            <p>
                Plataforma interna para administración de créditos, agencias, usuarios y auditoría.
                Acceso restringido únicamente a personal autorizado.
            </p>
        </div>

        <p style="font-size:13px; opacity:.68;">
            Seguridad base activa · Roles y permisos · Auditoría inicial
        </p>
    </section>

    <main class="auth-card-wrap">
        <section class="auth-card">
            <div>
                <p class="muted" style="margin:0 0 8px; font-weight:750;">Acceso administrativo</p>
                <h2 class="page-title">Iniciar sesión</h2>
                <p class="page-subtitle">Ingresa con tu usuario autorizado para continuar.</p>
            </div>

            @if (session('status'))
                <div class="status-box">{{ session('status') }}</div>
            @endif

            @if (isset($errors) && $errors->any())
                <div class="error-box">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" style="margin-top:22px;">
                @csrf

                <label class="form-group">
                    <span class="label">Correo electrónico</span>
                    <input
                        class="input"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        autocomplete="username"
                        required
                        autofocus
                    >
                </label>

                <label class="form-group">
                    <span class="label">Contraseña</span>
                    <input
                        class="input"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    >
                </label>

                <label style="display:flex; gap:10px; align-items:center; margin-top:16px; color:#4b5563; font-size:14px;">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    Mantener sesión iniciada
                </label>

                <button class="btn btn-primary" type="submit" style="width:100%; margin-top:22px;">
                    Entrar al sistema
                </button>
            </form>

            <p class="muted" style="font-size:12px; line-height:1.5; margin-top:20px;">
                Los accesos quedan registrados para fines de auditoría y seguridad.
            </p>
        </section>
    </main>
</div>
@endsection
