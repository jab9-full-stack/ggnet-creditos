@extends('layouts.app')

@section('content')
<div class="app-shell">
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-mark">B</div>
            <div>
                <div>BIENESTAR</div>
                <div style="font-size:12px; color:rgba(255,255,255,.58); font-weight:600;">GGNET Créditos</div>
            </div>
        </div>

        <nav class="nav-group">
            <a class="nav-link active" href="{{ route('dashboard') }}">Dashboard</a>
            <span class="nav-link" style="opacity:.45; cursor:not-allowed;">Usuarios</span>
            <span class="nav-link" style="opacity:.45; cursor:not-allowed;">Agencias</span>
            <span class="nav-link" style="opacity:.45; cursor:not-allowed;">Auditoría</span>
        </nav>

        <form method="POST" action="{{ route('logout') }}" style="margin-top:auto;">
            @csrf
            <button class="btn btn-ghost" type="submit" style="width:100%;">
                Cerrar sesión
            </button>
        </form>
    </aside>

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-title">Dashboard inicial</h1>
                <p class="page-subtitle">Core base activo: seguridad, roles, agencias, settings y auditoría.</p>
            </div>

            <div style="text-align:right;">
                <strong>{{ auth()->user()->name }}</strong>
                <div class="muted" style="font-size:13px;">{{ auth()->user()->email }}</div>
            </div>
        </header>

        <section class="grid grid-3">
            <div class="metric">
                <span>Usuarios</span>
                <strong>{{ $metrics['users'] }}</strong>
            </div>
            <div class="metric">
                <span>Agencias</span>
                <strong>{{ $metrics['agencies'] }}</strong>
            </div>
            <div class="metric">
                <span>Roles</span>
                <strong>{{ $metrics['roles'] }}</strong>
            </div>
            <div class="metric">
                <span>Permisos</span>
                <strong>{{ $metrics['permissions'] }}</strong>
            </div>
            <div class="metric">
                <span>Settings</span>
                <strong>{{ $metrics['settings'] }}</strong>
            </div>
            <div class="metric">
                <span>Auditoría</span>
                <strong>{{ $metrics['audit_logs'] }}</strong>
            </div>
        </section>

        <section class="panel" style="margin-top:22px;">
            <div class="panel-body">
                <h2 style="margin:0 0 14px; font-size:18px;">Últimos eventos de auditoría</h2>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Evento</th>
                            <th>Módulo</th>
                            <th>Usuario</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentAuditLogs as $log)
                            <tr>
                                <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                                <td>{{ $log->event }}</td>
                                <td>{{ $log->module ?? '—' }}</td>
                                <td>{{ $log->user?->name ?? 'Sistema' }}</td>
                                <td>{{ $log->ip_address ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="muted">Sin eventos registrados todavía.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
@endsection
