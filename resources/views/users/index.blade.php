@extends('layouts.app')

@section('content')
<div class="app-shell">
    @include('partials.sidebar')

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-title">Usuarios</h1>
                <p class="page-subtitle">Administra accesos, agencias, roles y estado operativo.</p>
            </div>

            @can('users.create')
                <a class="btn btn-primary" href="{{ route('users.create') }}">Nuevo usuario</a>
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
                <form method="GET" action="{{ route('users.index') }}" style="display:flex; gap:12px; align-items:center; margin-bottom:18px;">
                    <input
                        class="input"
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Buscar por nombre o correo..."
                    >

                    <select class="input" name="status" style="max-width:180px;">
                        <option value="">Todos</option>
                        <option value="active" @selected($status === 'active')>Activos</option>
                        <option value="inactive" @selected($status === 'inactive')>Inactivos</option>
                        <option value="blocked" @selected($status === 'blocked')>Bloqueados</option>
                    </select>

                    <button class="btn btn-primary" type="submit">Buscar</button>

                    @if ($search !== '' || $status !== '')
                        <a class="btn" style="background:#eef2f7;" href="{{ route('users.index') }}">Limpiar</a>
                    @endif
                </form>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Agencia</th>
                            <th>Roles</th>
                            <th>Estado</th>
                            <th>Último acceso</th>
                            <th style="text-align:right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>
                                    <strong>{{ $user->name }}</strong>
                                    <div class="muted">{{ $user->email }}</div>
                                </td>
                                <td>
                                    @if ($user->agency)
                                        <strong>{{ $user->agency->code }}</strong>
                                        <div class="muted">{{ $user->agency->name }}</div>
                                    @else
                                        <span class="muted">Sin agencia</span>
                                    @endif
                                </td>
                                <td>
                                    @forelse ($user->roles as $role)
                                        <span style="display:inline-flex; margin:2px; padding:5px 8px; border-radius:999px; background:#eef2f7; font-size:12px; font-weight:800;">
                                            {{ \App\Support\RoleLabels::label($role->name) }}
                                        </span>
                                    @empty
                                        <span class="muted">Sin roles</span>
                                    @endforelse
                                </td>
                                <td>
                                    @if ($user->status === 'active' && ! $user->blocked_at)
                                        <span style="color:var(--success); font-weight:800;">Activo</span>
                                    @elseif ($user->status === 'blocked')
                                        <span style="color:var(--danger); font-weight:800;">Bloqueado</span>
                                        <div class="muted">{{ $user->blocked_reason ?: 'Sin motivo registrado' }}</div>
                                    @else
                                        <span style="color:var(--warning); font-weight:800;">Inactivo</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $user->last_login_at?->format('d/m/Y H:i') ?? '—' }}
                                    <div class="muted">{{ $user->last_login_ip ?? '' }}</div>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:flex; justify-content:flex-end; gap:8px;">
                                        @can('users.update')
                                            <a class="btn" style="background:#eef2f7;" href="{{ route('users.edit', $user) }}">Editar</a>
                                        @endcan

                                        @can('users.delete')
                                            @unless(auth()->user()->is($user))
                                                <form method="POST" action="{{ route('users.destroy', $user) }}" data-confirm="true" data-confirm-title="Eliminar usuario" data-confirm-message="Esta acción eliminará el usuario del listado activo. Se conservará auditoría del movimiento.">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn" style="background:#fee2e2; color:var(--danger);" type="submit">Eliminar</button>
                                                </form>
                                            @endunless
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="muted">No hay usuarios registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div style="margin-top:18px;">
                    {{ $users->links() }}
                </div>
            </div>
        </section>
    </main>
</div>
@endsection
