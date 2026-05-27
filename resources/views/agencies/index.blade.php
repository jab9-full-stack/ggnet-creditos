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
            <div hidden data-toast-type="success" data-toast-title="Operación completada" data-toast-message="{{ session('status') }}"></div>
        @endif

        @if (session('error'))
            <div hidden data-toast-type="error" data-toast-title="No se pudo completar" data-toast-message="{{ session('error') }}"></div>
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

                <div class="table-scroll">
                    <div class="desktop-table table-scroll">
                    <table class="table compact-table">
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
                                        <div style="display:flex; justify-content:flex-end; gap:8px;">
                                            @can('agencies.update')
                                                <a class="btn" style="background:#eef2f7;" href="{{ route('agencies.edit', $agency) }}">Editar</a>
                                            @endcan

                                            @can('agencies.delete')
                                                <form method="POST" action="{{ route('agencies.destroy', $agency) }}" data-confirm="true" data-confirm-title="Eliminar agencia" data-confirm-message="Esta acción eliminará la agencia si no tiene usuarios asignados.">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn" style="background:#fee2e2; color:var(--danger);" type="submit">Eliminar</button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="muted">No hay agencias registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mobile-list">
                    @forelse ($agencies as $agency)
                        <article class="mobile-card">
                            <div class="mobile-card-title">{{ $agency->name }}</div>
                            <div class="mobile-card-subtitle">{{ $agency->email ?? 'Sin correo' }}</div>

                            <div style="margin-top:10px;">
                                @if ($agency->is_active)
                                    <span style="color:var(--success); font-weight:800;">Activa</span>
                                @else
                                    <span style="color:var(--danger); font-weight:800;">Inactiva</span>
                                @endif
                            </div>

                            <div class="mobile-card-grid">
                                <div><span class="mobile-field-label">Código</span><span class="mobile-field-value">{{ $agency->code }}</span></div>
                                <div><span class="mobile-field-label">Teléfono</span><span class="mobile-field-value">{{ $agency->phone ?? '—' }}</span></div>
                                <div><span class="mobile-field-label">Razón social</span><span class="mobile-field-value">{{ $agency->legal_name ?? '—' }}</span></div>
                                <div><span class="mobile-field-label">Ubicación</span><span class="mobile-field-value">{{ $agency->city ?? '—' }} · {{ $agency->department ?? $agency->country }}</span></div>
                            </div>

                            <div class="mobile-card-actions">
                                @can('agencies.update')
                                    <a class="btn" style="background:#eef2f7;" href="{{ route('agencies.edit', $agency) }}">Editar</a>
                                @endcan

                                @can('agencies.delete')
                                    <form method="POST" action="{{ route('agencies.destroy', $agency) }}" data-confirm="true" data-confirm-title="Eliminar agencia" data-confirm-message="Esta acción eliminará la agencia si no tiene usuarios asignados.">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn" style="background:#fee2e2; color:var(--danger);" type="submit">Eliminar</button>
                                    </form>
                                @endcan
                            </div>
                        </article>
                    @empty
                        <p class="muted">No hay agencias registradas.</p>
                    @endforelse
                </div>

                <div style="margin-top:18px;">
                    {{ $agencies->links() }}
                </div>
            </div>
        </section>
    </main>
</div>
@endsection
