@extends('layouts.app')

@section('content')
<div class="app-shell">
    @include('partials.sidebar')

    <main class="main">

        @if ($client->credit_blocked_at)
            <section class="panel" style="margin-bottom:18px; border-color:#fed7aa; background:#fff7ed;">
                <div class="panel-body">
                    <h2 style="margin:0 0 6px; font-size:18px; color:#9a3412;">Bloqueado para nuevo crédito</h2>
                    <p style="margin:0; color:#7c2d12;">
                        Este cliente tiene atraso registrado. No se cobra recargo de mora, pero queda bloqueado para nuevos créditos.
                    </p>
                    <p class="muted" style="margin:8px 0 0;">
                        {{ $client->credit_block_reason ?: 'Atraso registrado en cartera.' }}
                    </p>
                </div>
            </section>
        @endif


        <header class="topbar">
            <div>
                <h1 class="page-title">Expediente del cliente</h1>
                <p class="page-subtitle">{{ $client->code }} · {{ $client->fullName() }}</p>
            </div>

            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <a class="btn" style="background:#eef2f7;" href="{{ route('clients.index') }}">Volver a clientes</a>

                @can('clients.update')
                    <a class="btn" style="background:#eef2f7;" href="{{ route('clients.edit', $client) }}">Editar cliente</a>
                @endcan

                @can('client_references.view')
                    <a class="btn" style="background:#eef2f7;" href="{{ route('clients.references.index', $client) }}">Referencias</a>
                @endcan

                @can('client_documents.view')
                    <a class="btn btn-primary" href="{{ route('clients.documents.index', $client) }}">Documentos</a>
                @endcan
            </div>
        </header>

        @if (session('status'))
            <div hidden data-toast-type="success" data-toast-title="Operación completada" data-toast-message="{{ session('status') }}"></div>
        @endif

        <section class="grid grid-3" style="margin-bottom:18px;">
            <div class="metric">
                <span>Referencias</span>
                <strong>{{ $referenceCount }}</strong>
            </div>

            <div class="metric">
                <span>Documentos</span>
                <strong>{{ $documentCount }}</strong>
            </div>

            <div class="metric">
                <span>Checklist</span>
                <strong>{{ $completedChecks }}/{{ $totalChecks }}</strong>
            </div>
        </section>

        <section class="panel" style="margin-bottom:18px;">
            <div class="panel-body">
                <h2 style="margin:0 0 6px; font-size:18px;">Resumen</h2>
                <p class="muted" style="margin:0 0 16px;">Datos principales del expediente base.</p>

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
                        <span class="mobile-field-label">NIT</span>
                        <span class="mobile-field-value">{{ $client->nit ?: '—' }}</span>
                    </div>

                    <div>
                        <span class="mobile-field-label">Teléfono</span>
                        <span class="mobile-field-value">{{ $client->phone }}</span>
                    </div>

                    <div>
                        <span class="mobile-field-label">Correo</span>
                        <span class="mobile-field-value">{{ $client->email ?: 'Sin correo' }}</span>
                    </div>

                    <div>
                        <span class="mobile-field-label">Agencia</span>
                        <span class="mobile-field-value">{{ $client->agency?->name ?? 'Sin agencia' }}</span>
                    </div>

                    <div>
                        <span class="mobile-field-label">Estado</span>
                        <span class="mobile-field-value">
                            @if ($client->status === 'active')
                                <span style="color:var(--success); font-weight:800;">Activo</span>
                            @else
                                <span style="color:var(--warning); font-weight:800;">Inactivo</span>
                            @endif
                        </span>
                    </div>

                    <div>
                        <span class="mobile-field-label">Ocupación</span>
                        <span class="mobile-field-value">{{ $client->occupation ?: '—' }}</span>
                    </div>

                    <div>
                        <span class="mobile-field-label">Lugar de trabajo</span>
                        <span class="mobile-field-value">{{ $client->workplace ?: '—' }}</span>
                    </div>

                    <div class="span-3">
                        <span class="mobile-field-label">Dirección</span>
                        <span class="mobile-field-value">{{ $client->address_line }}</span>
                    </div>

                    <div class="span-3">
                        <span class="mobile-field-label">Observaciones</span>
                        <span class="mobile-field-value">{{ $client->notes ?: 'Sin observaciones' }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel" style="margin-bottom:18px;">
            <div class="panel-body">
                <h2 style="margin:0 0 6px; font-size:18px;">Checklist del expediente</h2>
                <p class="muted" style="margin:0 0 16px;">Control mínimo antes de pasar a solicitud de crédito. Todavía sin aprobar dinero, porque tampoco somos una máquina expendedora con base de datos.</p>

                <div class="grid" style="gap:10px;">
                    @foreach ($checklist as $item)
                        <div style="display:flex; align-items:flex-start; gap:12px; padding:14px; border:1px solid var(--line); border-radius:16px; background:#fff;">
                            <div style="width:28px; height:28px; border-radius:999px; display:grid; place-items:center; font-weight:900; {{ $item['ok'] ? 'background:#dcfce7; color:var(--success);' : 'background:#fef3c7; color:var(--warning);' }}">
                                {{ $item['ok'] ? '✓' : '!' }}
                            </div>

                            <div>
                                <strong>{{ $item['label'] }}</strong>
                                <div class="muted">{{ $item['hint'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <div class="grid grid-3" style="align-items:start;">
            <section class="panel" style="grid-column:span 1;">
                <div class="panel-body">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:12px;">
                        <div>
                            <h2 style="margin:0 0 4px; font-size:18px;">Referencias</h2>
                            <p class="muted" style="margin:0;">Últimas referencias registradas.</p>
                        </div>

                        @can('client_references.create')
                            <a class="btn" style="background:#eef2f7;" href="{{ route('clients.references.create', $client) }}">Agregar</a>
                        @endcan
                    </div>

                    <div class="grid" style="gap:10px;">
                        @forelse ($references as $reference)
                            <div style="padding:14px; border:1px solid var(--line); border-radius:16px;">
                                <strong>{{ $reference->full_name }}</strong>
                                <div class="muted">{{ $reference->relationship ?: 'Sin relación' }} · {{ $reference->phone }}</div>
                                @if ($reference->is_primary)
                                    <div style="margin-top:6px; color:var(--success); font-weight:800;">Principal</div>
                                @endif
                            </div>
                        @empty
                            <p class="muted">No hay referencias registradas.</p>
                        @endforelse
                    </div>

                    @can('client_references.view')
                        <div style="margin-top:14px;">
                            <a class="btn btn-primary" href="{{ route('clients.references.index', $client) }}">Ver referencias</a>
                        </div>
                    @endcan
                </div>
            </section>

            <section class="panel" style="grid-column:span 2;">
                <div class="panel-body">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:12px;">
                        <div>
                            <h2 style="margin:0 0 4px; font-size:18px;">Documentos</h2>
                            <p class="muted" style="margin:0;">Últimos documentos cargados al expediente privado.</p>
                        </div>

                        @can('client_documents.create')
                            <a class="btn" style="background:#eef2f7;" href="{{ route('clients.documents.create', $client) }}">Agregar</a>
                        @endcan
                    </div>

                    <div class="desktop-table table-scroll">
                        <table class="table compact-table">
                            <thead>
                                <tr>
                                    <th>Documento</th>
                                    <th>Archivo</th>
                                    <th>Estado</th>
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
                                        <td>
                                            {{ $document->original_name }}
                                            <div class="muted">{{ $document->readableSize() }}</div>
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
                                        <td style="text-align:right;">
                                            @can('client_documents.download')
                                                <a class="btn" style="background:#eef2f7;" href="{{ route('clients.documents.download', [$client, $document]) }}" download>Descargar</a>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="muted">No hay documentos registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mobile-list">
                        @forelse ($documents as $document)
                            <article class="mobile-card">
                                <div class="mobile-card-title">{{ $document->title }}</div>
                                <div class="mobile-card-subtitle">{{ $document->original_name }} · {{ $document->readableSize() }}</div>

                                <div style="margin-top:10px;">
                                    @if ($document->status === 'verified')
                                        <span style="color:var(--success); font-weight:800;">Verificado</span>
                                    @elseif ($document->status === 'rejected')
                                        <span style="color:var(--danger); font-weight:800;">Rechazado</span>
                                    @else
                                        <span style="color:var(--warning); font-weight:800;">Pendiente</span>
                                    @endif
                                </div>

                                <div class="mobile-card-actions">
                                    @can('client_documents.download')
                                        <a class="btn" style="background:#eef2f7;" href="{{ route('clients.documents.download', [$client, $document]) }}" download>Descargar</a>
                                    @endcan
                                </div>
                            </article>
                        @empty
                            <p class="muted">No hay documentos registrados.</p>
                        @endforelse
                    </div>

                    @can('client_documents.view')
                        <div style="margin-top:14px;">
                            <a class="btn btn-primary" href="{{ route('clients.documents.index', $client) }}">Ver documentos</a>
                        </div>
                    @endcan
                </div>
            </section>
        </div>
    </main>
</div>
@endsection
