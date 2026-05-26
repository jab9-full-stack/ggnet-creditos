@extends('layouts.app')

@section('content')
<div class="auth-shell">
    <section class="auth-brand">
        <div class="brand-pill">BIENESTAR · GGNET Créditos</div>
        <div>
            <h1>Sistema interno de gestión.</h1>
            <p>Redirigiendo al acceso seguro del sistema.</p>
        </div>
    </section>
    <main class="auth-card-wrap">
        <section class="auth-card">
            <h2 class="page-title">Bienvenido</h2>
            <p class="page-subtitle">Continúa al inicio de sesión para acceder.</p>
            <a class="btn btn-primary" href="{{ route('login') }}" style="width:100%; margin-top:22px;">Ir al login</a>
        </section>
    </main>
</div>
@endsection
