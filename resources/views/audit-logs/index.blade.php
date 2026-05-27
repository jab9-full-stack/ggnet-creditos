@extends('layouts.app')

@section('content')
<div class="app-shell">
    @include('partials.sidebar')

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-title">Bitácora de seguridad</h1>
                <p class="page-subtitle">Registro de accesos, cambios administrativos y acciones sensibles del sistema.</p>
            </div>
        </header>

        <section class="panel" style="margin-bottom:18px;">
            <div class="panel-body">
                <form method="GET" action="{{ route('audit-logs.index') }}" class="form-grid-uniform">
                    <label class="form-group">
                        <span class="label">Módulo</span>
                        <select class="input" name="module">
                            <option value="">Todos</option>
                            @foreach ($modules as $module)
                                <option value="{{ $module }}" @selected($filters['module'] === $module)>
                                    {{ \App\Support\Audit\AuditLogPresenter::moduleLabel($module) }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="form-group">
                        <span class="label">Evento</span>
                        <select class="input" name="event">
                            <option value="">Todos</option>
                            @foreach ($events as $event)
                                <option value="{{ $event }}" @selected($filters['event'] === $event)>
                                    {{ \App\Support\Audit\AuditLogPresenter::eventLabel($event) }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="form-group">
                        <span class="label">Usuario</span>
                        <select class="input" name="user_id">
                            <option value="">Todos</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected((string) $filters['user_id'] === (string) $user->id)>
                                    {{ $user->name }} · {{ $user->email }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="form-group">
                        <span class="label">Desde</span>
                        <input class="input" type="date" name="date_from" value="{{ $filters['date_from'] }}">
                    </label>

                    <label class="form-group">
                        <span class="label">Hasta</span>
                        <input class="input" type="date" name="date_to" value="{{ $filters['date_to'] }}">
                    </label>

                    <div style="display:flex; gap:10px; align-items:end;">
                        <button class="btn btn-primary" type="submit">Filtrar</button>
                        <a class="btn" style="background:#eef2f7;" href="{{ route('audit-logs.index') }}">Limpiar</a>
                    </div>
                </form>
            </div>
        </section>

        <section class="panel admin-desktop-only">
            <div class="panel-body">
                <div class="table-scroll">
                    <table class="table compact-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Acción</th>
                                <th>Módulo</th>
                                <th>Usuario</th>
                                <th>Entidad</th>
                                <th>IP</th>
                                <th style="text-align:right;">Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                @php
                                    $changes = \App\Support\Audit\AuditLogPresenter::readableChanges($log);
                                    $context = \App\Support\Audit\AuditLogPresenter::readableContext($log);
                                    $payload = \App\Support\Audit\AuditLogPresenter::technicalPayload($log);
                                @endphp

                                <tr>
                                    <td style="white-space:nowrap;">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                                    <td><strong>{{ \App\Support\Audit\AuditLogPresenter::eventLabel($log->event) }}</strong></td>
                                    <td>{{ \App\Support\Audit\AuditLogPresenter::moduleLabel($log->module) }}</td>
                                    <td>
                                        {{ $log->user?->name ?? 'Sistema' }}
                                        <div class="muted">{{ $log->user?->email }}</div>
                                    </td>
                                    <td>{{ \App\Support\Audit\AuditLogPresenter::entityLabel($log) }}</td>
                                    <td style="white-space:nowrap;">{{ $log->ip_address ?? '—' }}</td>
                                    <td style="text-align:right;">
                                        <button
                                            class="btn"
                                            style="background:#eef2f7;"
                                            type="button"
                                            data-audit-detail="true"
                                            data-title="{{ e(\App\Support\Audit\AuditLogPresenter::summary($log)) }}"
                                            data-changes='@json($changes)'
                                            data-context='@json($context)'
                                            data-payload="{{ e($payload) }}"
                                        >
                                            Ver
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="muted">No hay eventos de auditoría para los filtros seleccionados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div style="margin-top:18px;">
                    {{ $logs->links() }}
                </div>
            </div>
        </section>

        <section class="admin-mobile-only">
            @forelse ($logs as $log)
                @php
                    $changes = \App\Support\Audit\AuditLogPresenter::readableChanges($log);
                    $context = \App\Support\Audit\AuditLogPresenter::readableContext($log);
                    $payload = \App\Support\Audit\AuditLogPresenter::technicalPayload($log);
                @endphp

                <article class="mobile-card">
                    <div class="mobile-card-title">
                        {{ \App\Support\Audit\AuditLogPresenter::eventLabel($log->event) }}
                    </div>

                    <div class="mobile-card-subtitle">
                        {{ $log->created_at?->format('d/m/Y H:i:s') }}
                    </div>

                    <div class="mobile-card-grid">
                        <div>
                            <span class="mobile-field-label">Módulo</span>
                            <span class="mobile-field-value">{{ \App\Support\Audit\AuditLogPresenter::moduleLabel($log->module) }}</span>
                        </div>

                        <div>
                            <span class="mobile-field-label">Usuario</span>
                            <span class="mobile-field-value">{{ $log->user?->name ?? 'Sistema' }}</span>
                        </div>

                        <div>
                            <span class="mobile-field-label">Entidad</span>
                            <span class="mobile-field-value">{{ \App\Support\Audit\AuditLogPresenter::entityLabel($log) }}</span>
                        </div>

                        <div>
                            <span class="mobile-field-label">IP</span>
                            <span class="mobile-field-value">{{ $log->ip_address ?? '—' }}</span>
                        </div>
                    </div>

                    <div class="mobile-card-actions">
                        <button
                            class="btn"
                            style="background:#eef2f7;"
                            type="button"
                            data-audit-detail="true"
                            data-title="{{ e(\App\Support\Audit\AuditLogPresenter::summary($log)) }}"
                            data-changes='@json($changes)'
                            data-context='@json($context)'
                            data-payload="{{ e($payload) }}"
                        >
                            Ver detalle
                        </button>
                    </div>
                </article>
            @empty
                <section class="panel">
                    <div class="panel-body muted">No hay eventos de auditoría para los filtros seleccionados.</div>
                </section>
            @endforelse

            <div style="margin-top:18px;">
                {{ $logs->links() }}
            </div>
        </section>
    </main>
</div>
@endsection
