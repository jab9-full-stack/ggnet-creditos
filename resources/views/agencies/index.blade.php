@extends('layouts.app')

@section('content')
<div class="app-shell">
    @include('partials.sidebar')

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-title">Agencias</h1>
                <p class="page-subtitle">Administra las agencias operativas del sistema.</p>
            </div>

            @can('agencies.create')
                <a class="btn btn-primary" href="{{ route('agencies.create') }}">Nueva agencia</a>
            @endcan
        </header>

        @if (session('status'))
            <div class="status-box" style="margin-bottom:18px;">{{ session('status') }}</div>
        @endif

        <section class="panel">
            <div class="panel-body">
                <form method="GET" action="{{ route('agencies.index') }}" style="display:flex; gap:12px; align-items:center; margin-bottom:18px;">
                    <input
                        class="input"
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Buscar por código, nombre, correo o teléfono..."
                    >
                    <button class="btn btn-primary" type="submit">Buscar</button>
                    @if ($search !== '')
                        <a class="btn" style="background:#eef2f7;" href="{{ route('agencies.index') }}">Limpiar</a>
                    @endif
                </form>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Agencia</th>
                            <th>Contacto</th>
                            <th>Ubicación</th>
                            <th>Estado</th>
                            <th style="text-align:right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($agencies as $agency)
                            <tr>
                                <td><strong>{{ $agency->code }}</strong></td>
                                <td>
                                    <strong>{{ $agency->name }}</strong>
                                    <div class="muted">{{ $agency->legal_name ?? '—' }}</div>
                                </td>
                                <td>
                                    <div>{{ $agency->email ?? '—' }}</div>
                                    <div class="muted">{{ $agency->phone ?? '—' }}</div>
                                </td>
                                <td>
                                    <div>{{ $agency->city ?? '—' }}</div>
                                    <div class="muted">{{ $agency->department ?? $agency->country }}</div>
                                </td>
                                <td>
                                    @if ($agency->is_active)
                                        <span style="color:var(--success); font-weight:800;">Activa</span>
                                    @else
                                        <span style="color:var(--danger); font-weight:800;">Inactiva</span>
                                    @endif
                                </td>
                                <td style="text-align:right;">
                                    @can('agencies.update')
                                        <a class="btn" style="background:#eef2f7;" href="{{ route('agencies.edit', $agency) }}">Editar</a>
                                    @else
                                        <span class="muted">Sin acciones</span>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="muted">No hay agencias registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div style="margin-top:18px;">
                    {{ $agencies->links() }}
                </div>
            </div>
        </section>
    </main>
</div>
@endsection
