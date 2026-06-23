@extends('layouts.app')

@section('content')
<div class="app-shell">
    @include('partials.sidebar')

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-title">Detalle de solicitud</h1>
                <p class="page-subtitle">{{ $creditRequest->code }} · {{ $client->fullName() }}</p>
            </div>

            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <a class="btn" style="background:#eef2f7;" href="{{ route('credit-requests.index') }}">Volver</a>

                @can('clients.view')
                    <a class="btn" style="background:#eef2f7;" href="{{ route('clients.show', $client) }}">Ver expediente</a>
                @endcan

                @can('credit_requests.update')
                    @if ($creditRequest->canBeUpdated())
                        <a class="btn" style="background:#eef2f7;" href="{{ route('credit-requests.edit', $creditRequest) }}">Editar</a>
                    @endif
                @endcan

                @can('credit_requests.delete')
                    @if ($creditRequest->canBeDeleted())
                        <form method="POST" action="{{ route('credit-requests.destroy', $creditRequest) }}" data-confirm="true" data-confirm-title="Eliminar solicitud" data-confirm-message="Esta acción eliminará la solicitud del listado activo. Se conservará auditoría.">
                            @csrf
                            @method('DELETE')
                            <button class="btn" style="background:#fee2e2; color:var(--danger);" type="submit">Eliminar</button>
                        </form>
                    @endif
                @endcan
            </div>
        </header>

        @if (session('status'))
            <div hidden data-toast-type="success" data-toast-title="Operación completada" data-toast-message="{{ session('status') }}"></div>
        @endif

        @if (session('error'))
            <div hidden data-toast-type="error" data-toast-title="No se pudo completar" data-toast-message="{{ session('error') }}"></div>
        @endif

        <section class="grid grid-3" style="margin-bottom:18px;">
            <div class="metric">
                <span>Monto solicitado</span>
                <strong>Q {{ number_format((float) $creditRequest->requested_amount, 2) }}</strong>
            </div>

            <div class="metric">
                <span>Plazo</span>
                <strong>{{ $creditRequest->requested_term_weeks ? $creditRequest->requested_term_weeks.' sem.' : '—' }}</strong>
            </div>

            <div class="metric">
                <span>Estado</span>
                <strong style="{{ $creditRequest->statusStyle() }}">{{ $creditRequest->statusLabel() }}</strong>
            </div>
        </section>

        @include('credit-requests.partials.status-actions', [
            'creditRequest' => $creditRequest,
        ])

        <section class="panel" style="margin-bottom:18px;">
            <div class="panel-body">
                <h2 style="margin:0 0 6px; font-size:18px;">Solicitud</h2>
                <p class="muted" style="margin:0 0 16px;">Datos capturados para análisis. Todavía no existe crédito aprobado ni calendario.</p>

                <div class="form-grid-uniform">
                    <div>
                        <span class="mobile-field-label">Código</span>
                        <span class="mobile-field-value">{{ $creditRequest->code }}</span>
                    </div>

                    <div>
                        <span class="mobile-field-label">Agencia</span>
                        <span class="mobile-field-value">{{ $creditRequest->agency?->name ?? 'Sin agencia' }}</span>
                    </div>

                    <div>
                        <span class="mobile-field-label">Fecha</span>
                        <span class="mobile-field-value">{{ $creditRequest->created_at?->format('d/m/Y H:i') }}</span>
                    </div>

                    <div>
                        <span class="mobile-field-label">Ingreso mensual</span>
                        <span class="mobile-field-value">{{ $creditRequest->monthly_income ? 'Q '.number_format((float) $creditRequest->monthly_income, 2) : '—' }}</span>
                    </div>

                    <div>
                        <span class="mobile-field-label">Fuente de ingresos</span>
                        <span class="mobile-field-value">{{ $creditRequest->income_source ?: '—' }}</span>
                    </div>

                    <div>
                        <span class="mobile-field-label">Creado por</span>
                        <span class="mobile-field-value">{{ $creditRequest->createdBy?->name ?? 'Sistema' }}</span>
                    </div>

                    <div class="span-3">
                        <span class="mobile-field-label">Destino / propósito</span>
                        <span class="mobile-field-value">{{ $creditRequest->purpose ?: 'Sin propósito registrado' }}</span>
                    </div>

                    <div class="span-3">
                        <span class="mobile-field-label">Observaciones</span>
                        <span class="mobile-field-value">{{ $creditRequest->notes ?: 'Sin observaciones' }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel" style="margin-bottom:18px;">
            <div class="panel-body">
                <h2 style="margin:0 0 6px; font-size:18px;">Cliente asociado</h2>
                <p class="muted" style="margin:0 0 16px;">Resumen del expediente base.</p>

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
                        <span class="mobile-field-label">Teléfono</span>
                        <span class="mobile-field-value">{{ $client->phone }}</span>
                    </div>

                    <div>
                        <span class="mobile-field-label">Referencias</span>
                        <span class="mobile-field-value">{{ $referenceCount }}</span>
                    </div>

                    <div>
                        <span class="mobile-field-label">Documentos</span>
                        <span class="mobile-field-value">{{ $documentCount }}</span>
                    </div>

                    <div>
                        <span class="mobile-field-label">Documentos verificados</span>
                        <span class="mobile-field-value">{{ $verifiedDocumentCount }}</span>
                    </div>

                    <div class="span-3">
                        <span class="mobile-field-label">Dirección</span>
                        <span class="mobile-field-value">{{ $client->address_line }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel" style="margin-bottom:18px;">
            <div class="panel-body">
                <h2 style="margin:0 0 6px; font-size:18px;">Historial de estado</h2>
                <p class="muted" style="margin:0 0 16px;">Últimos eventos registrados para esta solicitud.</p>

                <div class="desktop-table table-scroll">
                    <table class="table compact-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Evento</th>
                                <th>Estado anterior</th>
                                <th>Estado nuevo</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($statusAuditLogs as $log)
                                <tr>
                                    <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                                    <td>{{ $log->event }}</td>
                                    <td>{{ $log->context['old_status'] ?? $log->old_values['status'] ?? '—' }}</td>
                                    <td>{{ $log->context['new_status'] ?? $log->new_values['status'] ?? '—' }}</td>
                                    <td>
                                        {{ $log->user?->name ?? 'Sistema' }}
                                        <div class="muted">{{ $log->user?->email }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="muted">Sin eventos de estado registrados todavía.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mobile-list">
                    @forelse ($statusAuditLogs as $log)
                        <article class="mobile-card">
                            <div class="mobile-card-title">{{ $log->event }}</div>
                            <div class="mobile-card-subtitle">{{ $log->created_at?->format('d/m/Y H:i') }} · {{ $log->user?->name ?? 'Sistema' }}</div>
                            <div class="mobile-card-grid">
                                <div><span class="mobile-field-label">Anterior</span><span class="mobile-field-value">{{ $log->context['old_status'] ?? $log->old_values['status'] ?? '—' }}</span></div>
                                <div><span class="mobile-field-label">Nuevo</span><span class="mobile-field-value">{{ $log->context['new_status'] ?? $log->new_values['status'] ?? '—' }}</span></div>
                            </div>
                        </article>
                    @empty
                        <p class="muted">Sin eventos de estado registrados todavía.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <div class="grid grid-3" style="align-items:start;">
            <section class="panel" style="grid-column:span 1;">
                <div class="panel-body">
                    <h2 style="margin:0 0 6px; font-size:18px;">Referencias recientes</h2>
                    <p class="muted" style="margin:0 0 12px;">Vista rápida, no sustituye revisión formal.</p>

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
                </div>
            </section>

            <section class="panel" style="grid-column:span 2;">
                <div class="panel-body">
                    <h2 style="margin:0 0 6px; font-size:18px;">Documentos recientes</h2>
                    <p class="muted" style="margin:0 0 12px;">Documentos disponibles en el expediente privado.</p>

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
                </div>
            </section>
        </div>
    </main>
</div>
@endsection
