@extends('layouts.app')

@section('content')
<div class="app-shell">
    @include('partials.sidebar')

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-title">Documentos</h1>
                <p class="page-subtitle">{{ $client->code }} · {{ $client->fullName() }}</p>
            </div>

            <div style="display:flex; gap:10px; align-items:center;">
                <a class="btn" style="background:#eef2f7;" href="{{ route('clients.index') }}">Volver a clientes</a>

                @can('client_documents.create')
                    <a class="btn btn-primary" href="{{ route('clients.documents.create', $client) }}">Nuevo documento</a>
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
                <form method="GET" action="{{ route('clients.documents.index', $client) }}" class="form-grid-uniform" style="margin-bottom:18px;">
                    <label class="form-group">
                        <span class="label">Buscar</span>
                        <input class="input" type="search" name="search" value="{{ $search }}" placeholder="Título, nombre de archivo u observaciones...">
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

                    <label class="form-group">
                        <span class="label">Estado</span>
                        <select class="input" name="status">
                            <option value="">Todos</option>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <div style="display:flex; gap:10px; align-items:end;">
                        <button class="btn btn-primary" type="submit">Buscar</button>

                        @if ($search !== '' || $type !== '' || $status !== '')
                            <a class="btn" style="background:#eef2f7;" href="{{ route('clients.documents.index', $client) }}">Limpiar</a>
                        @endif
                    </div>
                </form>

                <div class="desktop-table table-scroll">
                    <table class="table compact-table">
                        <thead>
                            <tr>
                                <th>Documento</th>
                                <th>Tipo</th>
                                <th>Archivo</th>
                                <th>Estado</th>
                                <th>Subido por</th>
                                <th>Verificación</th>
                                <th style="text-align:right;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($documents as $document)
                                <tr>
                                    <td>
                                        <strong>{{ $document->title }}</strong>
                                        <div class="muted">{{ $document->created_at?->format('d/m/Y H:i') }}</div>
                                    </td>
                                    <td>{{ $types[$document->type] ?? $document->type }}</td>
                                    <td>
                                        <div>{{ $document->original_name }}</div>
                                        <div class="muted">{{ $document->mime_type ?: '—' }} · {{ $document->readableSize() }}</div>
                                    </td>
                                    <td>
                                        @if ($document->status === 'verified')
                                            <span style="color:var(--success); font-weight:800;">Verificado</span>
                                        @elseif ($document->status === 'rejected')
                                            <span style="color:var(--danger); font-weight:800;">Rechazado</span>
                                        @else
                                            <span style="color:var(--warning); font-weight:800;">Pendiente</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $document->uploadedBy?->name ?? 'Sistema' }}
                                        <div class="muted">{{ $document->uploadedBy?->email }}</div>
                                    </td>
                                    <td>
                                        @if ($document->verified_at)
                                            {{ $document->verified_at?->format('d/m/Y H:i') }}
                                            <div class="muted">{{ $document->verifiedBy?->name ?? '—' }}</div>
                                        @else
                                            <span class="muted">Sin verificar</span>
                                        @endif
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:flex; justify-content:flex-end; gap:8px;">
                                            @can('client_documents.download')
                                                <a class="btn" style="background:#eef2f7;" href="{{ route('clients.documents.download', [$client, $document]) }}" download>Descargar</a>
                                            @endcan

                                            @can('client_documents.update')
                                                <a class="btn" style="background:#eef2f7;" href="{{ route('clients.documents.edit', [$client, $document]) }}">Editar</a>
                                            @endcan

                                            @can('client_documents.delete')
                                                <form method="POST" action="{{ route('clients.documents.destroy', [$client, $document]) }}" data-confirm="true" data-confirm-title="Eliminar documento" data-confirm-message="El documento saldrá del expediente activo. El archivo físico se conservará para trazabilidad.">
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
                                    <td colspan="7" class="muted">No hay documentos registrados para este cliente.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mobile-list">
                    @forelse ($documents as $document)
                        <article class="mobile-card">
                            <div class="mobile-card-title">{{ $document->title }}</div>
                            <div class="mobile-card-subtitle">{{ $types[$document->type] ?? $document->type }} · {{ $document->original_name }}</div>

                            <div style="margin-top:10px;">
                                @if ($document->status === 'verified')
                                    <span style="color:var(--success); font-weight:800;">Verificado</span>
                                @elseif ($document->status === 'rejected')
                                    <span style="color:var(--danger); font-weight:800;">Rechazado</span>
                                @else
                                    <span style="color:var(--warning); font-weight:800;">Pendiente</span>
                                @endif
                            </div>

                            <div class="mobile-card-grid">
                                <div><span class="mobile-field-label">Tamaño</span><span class="mobile-field-value">{{ $document->readableSize() }}</span></div>
                                <div><span class="mobile-field-label">MIME</span><span class="mobile-field-value">{{ $document->mime_type ?: '—' }}</span></div>
                                <div><span class="mobile-field-label">Subido por</span><span class="mobile-field-value">{{ $document->uploadedBy?->name ?? 'Sistema' }}</span></div>
                                <div><span class="mobile-field-label">Fecha</span><span class="mobile-field-value">{{ $document->created_at?->format('d/m/Y H:i') }}</span></div>
                            </div>

                            <div class="mobile-card-actions">
                                @can('client_documents.download')
                                    <a class="btn" style="background:#eef2f7;" href="{{ route('clients.documents.download', [$client, $document]) }}" download>Descargar</a>
                                @endcan

                                @can('client_documents.update')
                                    <a class="btn" style="background:#eef2f7;" href="{{ route('clients.documents.edit', [$client, $document]) }}">Editar</a>
                                @endcan

                                @can('client_documents.delete')
                                    <form method="POST" action="{{ route('clients.documents.destroy', [$client, $document]) }}" data-confirm="true" data-confirm-title="Eliminar documento" data-confirm-message="El documento saldrá del expediente activo. El archivo físico se conservará para trazabilidad.">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn" style="background:#fee2e2; color:var(--danger);" type="submit">Eliminar</button>
                                    </form>
                                @endcan
                            </div>
                        </article>
                    @empty
                        <p class="muted">No hay documentos registrados para este cliente.</p>
                    @endforelse
                </div>

                <div style="margin-top:18px;">
                    {{ $documents->links() }}
                </div>
            </div>
        </section>
    </main>
</div>
@endsection
