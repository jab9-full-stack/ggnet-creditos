@extends('layouts.app')

@section('content')
<div class="app-shell">
    @include('partials.sidebar')

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-title">Clientes</h1>
                <p class="page-subtitle">Expediente base de clientes y solicitantes. Sin créditos todavía, porque no vamos a mezclar pólvora con café.</p>
            </div>

            @can('clients.create')
                <a class="btn btn-primary" href="{{ route('clients.create') }}">Nuevo cliente</a>
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
                <form method="GET" action="{{ route('clients.index') }}" class="form-grid-uniform" style="margin-bottom:18px;">
                    <label class="form-group">
                        <span class="label">Buscar</span>
                        <input
                            class="input"
                            type="search"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Nombre, DPI, NIT, teléfono, correo o código..."
                        >
                    </label>

                    <label class="form-group">
                        <span class="label">Agencia</span>
                        <select class="input" name="agency_id">
                            <option value="">Todas</option>
                            @foreach ($agencies as $agency)
                                <option value="{{ $agency->id }}" @selected((string) $agencyId === (string) $agency->id)>
                                    {{ $agency->code }} · {{ $agency->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="form-group">
                        <span class="label">Estado</span>
                        <select class="input" name="status">
                            <option value="">Todos</option>
                            <option value="active" @selected($status === 'active')>Activos</option>
                            <option value="inactive" @selected($status === 'inactive')>Inactivos</option>
                        </select>
                    </label>

                    <div style="display:flex; gap:10px; align-items:end;">
                        <button class="btn btn-primary" type="submit">Buscar</button>

                        @if ($search !== '' || $status !== '' || $agencyId !== '')
                            <a class="btn" style="background:#eef2f7;" href="{{ route('clients.index') }}">Limpiar</a>
                        @endif
                    </div>
                </form>

                <div class="desktop-table table-scroll">
                    <table class="table compact-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Identidad</th>
                                <th>Contacto</th>
                                <th>Agencia</th>
                                <th>Estado</th>
                                <th style="text-align:right;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($clients as $client)
                                <tr>
                                    <td><strong>{{ $client->code }}</strong></td>
                                    <td>
                                        <strong>{{ $client->fullName() }}</strong>
                                        <div class="muted">{{ $client->occupation ?: 'Sin ocupación registrada' }}</div>
                                    </td>
                                    <td>
                                        <div>DPI: {{ $client->dpi }}</div>
                                        <div class="muted">NIT: {{ $client->nit ?: '—' }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $client->phone }}</div>
                                        <div class="muted">{{ $client->email ?: 'Sin correo' }}</div>
                                    </td>
                                    <td>
                                        @if ($client->agency)
                                            <strong>{{ $client->agency->code }}</strong>
                                            <div class="muted">{{ $client->agency->name }}</div>
                                        @else
                                            <span class="muted">Sin agencia</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($client->status === 'active')
                                            <span style="color:var(--success); font-weight:800;">Activo</span>
                                        @else
                                            <span style="color:var(--warning); font-weight:800;">Inactivo</span>
                                        @endif
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:flex; justify-content:flex-end; gap:8px;">
                                            @can('client_references.view')
                                                <a class="btn" style="background:#eef2f7;" href="{{ route('clients.references.index', $client) }}">Referencias</a>
                                            @endcan

                                            @can('clients.update')
                                                <a class="btn" style="background:#eef2f7;" href="{{ route('clients.edit', $client) }}">Editar</a>
                                            @endcan

                                            @can('clients.delete')
                                                <form method="POST" action="{{ route('clients.destroy', $client) }}" data-confirm="true" data-confirm-title="Eliminar cliente" data-confirm-message="Esta acción eliminará el cliente del listado activo. Se conservará la auditoría.">
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
                                    <td colspan="7" class="muted">No hay clientes registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mobile-list">
                    @forelse ($clients as $client)
                        <article class="mobile-card">
                            <div class="mobile-card-title">{{ $client->fullName() }}</div>
                            <div class="mobile-card-subtitle">{{ $client->code }} · DPI {{ $client->dpi }}</div>

                            <div style="margin-top:10px;">
                                @if ($client->status === 'active')
                                    <span style="color:var(--success); font-weight:800;">Activo</span>
                                @else
                                    <span style="color:var(--warning); font-weight:800;">Inactivo</span>
                                @endif
                            </div>

                            <div class="mobile-card-grid">
                                <div><span class="mobile-field-label">Teléfono</span><span class="mobile-field-value">{{ $client->phone }}</span></div>
                                <div><span class="mobile-field-label">Correo</span><span class="mobile-field-value">{{ $client->email ?: 'Sin correo' }}</span></div>
                                <div><span class="mobile-field-label">Agencia</span><span class="mobile-field-value">{{ $client->agency?->name ?? 'Sin agencia' }}</span></div>
                                <div><span class="mobile-field-label">Dirección</span><span class="mobile-field-value">{{ $client->address_line }}</span></div>
                            </div>

                            <div class="mobile-card-actions">
                                @can('client_references.view')
                                    <a class="btn" style="background:#eef2f7;" href="{{ route('clients.references.index', $client) }}">Referencias</a>
                                @endcan

                                @can('clients.update')
                                    <a class="btn" style="background:#eef2f7;" href="{{ route('clients.edit', $client) }}">Editar</a>
                                @endcan

                                @can('clients.delete')
                                    <form method="POST" action="{{ route('clients.destroy', $client) }}" data-confirm="true" data-confirm-title="Eliminar cliente" data-confirm-message="Esta acción eliminará el cliente del listado activo. Se conservará la auditoría.">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn" style="background:#fee2e2; color:var(--danger);" type="submit">Eliminar</button>
                                    </form>
                                @endcan
                            </div>
                        </article>
                    @empty
                        <p class="muted">No hay clientes registrados.</p>
                    @endforelse
                </div>

                <div style="margin-top:18px;">
                    {{ $clients->links() }}
                </div>
            </div>
        </section>
    </main>
</div>
@endsection
