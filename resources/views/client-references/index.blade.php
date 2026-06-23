@extends('layouts.app')

@section('content')
<div class="app-shell">
    @include('partials.sidebar')

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-title">Referencias</h1>
                <p class="page-subtitle">
                    {{ $client->code }} · {{ $client->fullName() }}
                </p>
            </div>

            <div style="display:flex; gap:10px; align-items:center;">
                <a class="btn" style="background:#eef2f7;" href="{{ route('clients.index') }}">Volver a clientes</a>

                @can('client_references.create')
                    <a class="btn btn-primary" href="{{ route('clients.references.create', $client) }}">Nueva referencia</a>
                @endcan
            </div>
        </header>

        @if (session('status'))
            <div hidden data-toast-type="success" data-toast-title="Operación completada" data-toast-message="{{ session('status') }}"></div>
        @endif

        @if (session('error'))
            <div hidden data-toast-type="error" data-toast-title="No se pudo completar" data-toast-message="{{ session('error') }}"></div>
        @endif

        <section class="panel" style="margin-bottom:18px;">
            <div class="panel-body">
                <div class="form-grid-uniform">
                    <div>
                        <span class="mobile-field-label">Cliente</span>
                        <span class="mobile-field-value">{{ $client->fullName() }}</span>
                    </div>
                    <div>
                        <span class="mobile-field-label">DPI</span>
                        <span class="mobile-field-value">{{ $client->dpi }}</span>
                    </div>
                    <div>
                        <span class="mobile-field-label">Agencia</span>
                        <span class="mobile-field-value">{{ $client->agency?->name ?? 'Sin agencia' }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-body">
                <form method="GET" action="{{ route('clients.references.index', $client) }}" class="form-grid-uniform" style="margin-bottom:18px;">
                    <label class="form-group">
                        <span class="label">Buscar</span>
                        <input class="input" type="search" name="search" value="{{ $search }}" placeholder="Nombre, relación, teléfono o trabajo...">
                    </label>

                    <label class="form-group">
                        <span class="label">Tipo</span>
                        <select class="input" name="type">
                            <option value="">Todos</option>
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <div style="display:flex; gap:10px; align-items:end;">
                        <button class="btn btn-primary" type="submit">Buscar</button>

                        @if ($search !== '' || $type !== '')
                            <a class="btn" style="background:#eef2f7;" href="{{ route('clients.references.index', $client) }}">Limpiar</a>
                        @endif
                    </div>
                </form>

                <div class="desktop-table table-scroll">
                    <table class="table compact-table">
                        <thead>
                            <tr>
                                <th>Referencia</th>
                                <th>Tipo</th>
                                <th>Relación</th>
                                <th>Teléfonos</th>
                                <th>Trabajo</th>
                                <th>Principal</th>
                                <th style="text-align:right;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($references as $reference)
                                <tr>
                                    <td>
                                        <strong>{{ $reference->full_name }}</strong>
                                        <div class="muted">{{ $reference->address_line ?: 'Sin dirección registrada' }}</div>
                                    </td>
                                    <td>{{ $types[$reference->type] ?? $reference->type }}</td>
                                    <td>{{ $reference->relationship ?: '—' }}</td>
                                    <td>
                                        <div>{{ $reference->phone }}</div>
                                        <div class="muted">{{ $reference->secondary_phone ?: '—' }}</div>
                                    </td>
                                    <td>{{ $reference->workplace ?: '—' }}</td>
                                    <td>
                                        @if ($reference->is_primary)
                                            <span style="color:var(--success); font-weight:800;">Sí</span>
                                        @else
                                            <span class="muted">No</span>
                                        @endif
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:flex; justify-content:flex-end; gap:8px;">
                                            @can('client_references.update')
                                                <a class="btn" style="background:#eef2f7;" href="{{ route('clients.references.edit', [$client, $reference]) }}">Editar</a>
                                            @endcan

                                            @can('client_references.delete')
                                                <form method="POST" action="{{ route('clients.references.destroy', [$client, $reference]) }}" data-confirm="true" data-confirm-title="Eliminar referencia" data-confirm-message="Esta acción eliminará la referencia del expediente activo. Se conservará auditoría.">
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
                                    <td colspan="7" class="muted">No hay referencias registradas para este cliente.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mobile-list">
                    @forelse ($references as $reference)
                        <article class="mobile-card">
                            <div class="mobile-card-title">{{ $reference->full_name }}</div>
                            <div class="mobile-card-subtitle">{{ $types[$reference->type] ?? $reference->type }} · {{ $reference->relationship ?: 'Sin relación' }}</div>

                            <div style="margin-top:10px;">
                                @if ($reference->is_primary)
                                    <span style="color:var(--success); font-weight:800;">Referencia principal</span>
                                @else
                                    <span class="muted">Referencia secundaria</span>
                                @endif
                            </div>

                            <div class="mobile-card-grid">
                                <div><span class="mobile-field-label">Teléfono</span><span class="mobile-field-value">{{ $reference->phone }}</span></div>
                                <div><span class="mobile-field-label">Teléfono secundario</span><span class="mobile-field-value">{{ $reference->secondary_phone ?: '—' }}</span></div>
                                <div><span class="mobile-field-label">Trabajo</span><span class="mobile-field-value">{{ $reference->workplace ?: '—' }}</span></div>
                                <div><span class="mobile-field-label">Dirección</span><span class="mobile-field-value">{{ $reference->address_line ?: '—' }}</span></div>
                            </div>

                            <div class="mobile-card-actions">
                                @can('client_references.update')
                                    <a class="btn" style="background:#eef2f7;" href="{{ route('clients.references.edit', [$client, $reference]) }}">Editar</a>
                                @endcan

                                @can('client_references.delete')
                                    <form method="POST" action="{{ route('clients.references.destroy', [$client, $reference]) }}" data-confirm="true" data-confirm-title="Eliminar referencia" data-confirm-message="Esta acción eliminará la referencia del expediente activo. Se conservará auditoría.">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn" style="background:#fee2e2; color:var(--danger);" type="submit">Eliminar</button>
                                    </form>
                                @endcan
                            </div>
                        </article>
                    @empty
                        <p class="muted">No hay referencias registradas para este cliente.</p>
                    @endforelse
                </div>

                <div style="margin-top:18px;">
                    {{ $references->links() }}
                </div>
            </div>
        </section>
    </main>
</div>
@endsection
