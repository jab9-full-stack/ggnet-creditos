@extends('layouts.app')

@section('content')
<div class="app-shell">
    @include('partials.sidebar')

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-title">Solicitudes de crédito</h1>
                <p class="page-subtitle">Registro inicial de solicitudes. Todavía no hay cuotas, pagos ni caja, porque no estamos mezclando gasolina con fósforos.</p>
            </div>

            @can('credit_requests.create')
                <a class="btn btn-primary" href="{{ route('credit-requests.create') }}">Nueva solicitud</a>
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
                <form method="GET" action="{{ route('credit-requests.index') }}" class="form-grid-uniform" style="margin-bottom:18px;">
                    <label class="form-group">
                        <span class="label">Buscar</span>
                        <input class="input" type="search" name="search" value="{{ $search }}" placeholder="Código, cliente, DPI o teléfono...">
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
                        <span class="label">Desde</span>
                        <input class="input" type="date" name="date_from" value="{{ $dateFrom }}">
                    </label>

                    <label class="form-group">
                        <span class="label">Hasta</span>
                        <input class="input" type="date" name="date_to" value="{{ $dateTo }}">
                    </label>

                    <div style="display:flex; gap:10px; align-items:end;">
                        <button class="btn btn-primary" type="submit">Buscar</button>

                        @if ($search !== '' || $status !== '' || $agencyId !== '' || $dateFrom !== '' || $dateTo !== '')
                            <a class="btn" style="background:#eef2f7;" href="{{ route('credit-requests.index') }}">Limpiar</a>
                        @endif
                    </div>
                </form>

                <div class="desktop-table table-scroll">
                    <table class="table compact-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Agencia</th>
                                <th>Monto</th>
                                <th>Plazo</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th style="text-align:right;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($creditRequests as $creditRequest)
                                <tr>
                                    <td><strong>{{ $creditRequest->code }}</strong></td>
                                    <td>
                                        <strong>{{ $creditRequest->client?->fullName() ?? 'Cliente no disponible' }}</strong>
                                        <div class="muted">{{ $creditRequest->client?->code }} · DPI {{ $creditRequest->client?->dpi }}</div>
                                    </td>
                                    <td>
                                        @if ($creditRequest->agency)
                                            <strong>{{ $creditRequest->agency->code }}</strong>
                                            <div class="muted">{{ $creditRequest->agency->name }}</div>
                                        @else
                                            <span class="muted">Sin agencia</span>
                                        @endif
                                    </td>
                                    <td>Q {{ number_format((float) $creditRequest->requested_amount, 2) }}</td>
                                    <td>
                                        @if ($creditRequest->requested_term_weeks)
                                            {{ $creditRequest->requested_term_weeks }} semanas
                                        @else
                                            <span class="muted">Sin plazo</span>
                                        @endif
                                    </td>
                                    <td><span style="{{ $creditRequest->statusStyle() }}">{{ $creditRequest->statusLabel() }}</span></td>
                                    <td>{{ $creditRequest->created_at?->format('d/m/Y H:i') }}</td>
                                    <td style="text-align:right;">
                                        <div style="display:flex; justify-content:flex-end; gap:8px;">
                                            <a class="btn" style="background:#eef2f7;" href="{{ route('credit-requests.show', $creditRequest) }}">Ver</a>

                                            @can('credit_requests.update')
                                                @if ($creditRequest->canBeUpdated())
                                                    <a class="btn" style="background:#eef2f7;" href="{{ route('credit-requests.edit', $creditRequest) }}">Editar</a>
                                                @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="muted">No hay solicitudes de crédito registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mobile-list">
                    @forelse ($creditRequests as $creditRequest)
                        <article class="mobile-card">
                            <div class="mobile-card-title">{{ $creditRequest->code }} · {{ $creditRequest->client?->fullName() }}</div>
                            <div class="mobile-card-subtitle">DPI {{ $creditRequest->client?->dpi }} · {{ $creditRequest->created_at?->format('d/m/Y H:i') }}</div>

                            <div style="margin-top:10px;">
                                <span style="{{ $creditRequest->statusStyle() }}">{{ $creditRequest->statusLabel() }}</span>
                            </div>

                            <div class="mobile-card-grid">
                                <div><span class="mobile-field-label">Monto</span><span class="mobile-field-value">Q {{ number_format((float) $creditRequest->requested_amount, 2) }}</span></div>
                                <div><span class="mobile-field-label">Plazo</span><span class="mobile-field-value">{{ $creditRequest->requested_term_weeks ? $creditRequest->requested_term_weeks.' semanas' : 'Sin plazo' }}</span></div>
                                <div><span class="mobile-field-label">Agencia</span><span class="mobile-field-value">{{ $creditRequest->agency?->name ?? 'Sin agencia' }}</span></div>
                                <div><span class="mobile-field-label">Cliente</span><span class="mobile-field-value">{{ $creditRequest->client?->code }}</span></div>
                            </div>

                            <div class="mobile-card-actions">
                                <a class="btn" style="background:#eef2f7;" href="{{ route('credit-requests.show', $creditRequest) }}">Ver</a>

                                @can('credit_requests.update')
                                    @if ($creditRequest->canBeUpdated())
                                        <a class="btn" style="background:#eef2f7;" href="{{ route('credit-requests.edit', $creditRequest) }}">Editar</a>
                                    @endif
                                @endcan
                            </div>
                        </article>
                    @empty
                        <p class="muted">No hay solicitudes de crédito registradas.</p>
                    @endforelse
                </div>

                <div style="margin-top:18px;">
                    {{ $creditRequests->links() }}
                </div>
            </div>
        </section>
    </main>
</div>
@endsection
