@extends('layouts.app')

@section('content')
<div class="app-shell">
    @include('partials.sidebar')

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-title">Créditos</h1>
                <p class="page-subtitle">Créditos creados desde solicitudes aprobadas. Sin cuotas ni pagos todavía; el drama contable espera su turno.</p>
            </div>
        </header>

        @if (session('status'))
            <div hidden data-toast-type="success" data-toast-title="Operación completada" data-toast-message="{{ session('status') }}"></div>
        @endif

        <section class="panel">
            <div class="panel-body">
                <form method="GET" action="{{ route('credits.index') }}" class="form-grid-uniform" style="margin-bottom:18px;">
                    <label class="form-group">
                        <span class="label">Buscar</span>
                        <input class="input" type="search" name="search" value="{{ $search }}" placeholder="Crédito, solicitud, cliente, DPI o teléfono...">
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
                            <a class="btn" style="background:#eef2f7;" href="{{ route('credits.index') }}">Limpiar</a>
                        @endif
                    </div>
                </form>

                <div class="desktop-table table-scroll">
                    <table class="table compact-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Solicitud</th>
                                <th>Capital</th>
                                <th>Interés</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th style="text-align:right;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($credits as $credit)
                                <tr>
                                    <td><strong>{{ $credit->code }}</strong></td>
                                    <td>
                                        <strong>{{ $credit->client?->fullName() ?? 'Cliente no disponible' }}</strong>
                                        <div class="muted">{{ $credit->client?->code }} · DPI {{ $credit->client?->dpi }}</div>
                                    </td>
                                    <td>
                                        <strong>{{ $credit->creditRequest?->code }}</strong>
                                        <div class="muted">{{ $credit->creditRequest?->statusLabel() ?? '—' }}</div>
                                    </td>
                                    <td>Q {{ number_format((float) $credit->principal_amount, 2) }}</td>
                                    <td>{{ number_format((float) $credit->interest_rate_percent, 2) }}%</td>
                                    <td>Q {{ number_format((float) $credit->total_amount, 2) }}</td>
                                    <td><span style="{{ $credit->statusStyle() }}">{{ $credit->statusLabel() }}</span></td>
                                    <td style="text-align:right;">
                                        <a class="btn" style="background:#eef2f7;" href="{{ route('credits.show', $credit) }}">Ver</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="muted">No hay créditos registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mobile-list">
                    @forelse ($credits as $credit)
                        <article class="mobile-card">
                            <div class="mobile-card-title">{{ $credit->code }} · {{ $credit->client?->fullName() }}</div>
                            <div class="mobile-card-subtitle">Solicitud {{ $credit->creditRequest?->code }} · {{ $credit->created_at?->format('d/m/Y H:i') }}</div>

                            <div style="margin-top:10px;">
                                <span style="{{ $credit->statusStyle() }}">{{ $credit->statusLabel() }}</span>
                            </div>

                            <div class="mobile-card-grid">
                                <div><span class="mobile-field-label">Capital</span><span class="mobile-field-value">Q {{ number_format((float) $credit->principal_amount, 2) }}</span></div>
                                <div><span class="mobile-field-label">Interés</span><span class="mobile-field-value">{{ number_format((float) $credit->interest_rate_percent, 2) }}%</span></div>
                                <div><span class="mobile-field-label">Interés monto</span><span class="mobile-field-value">Q {{ number_format((float) $credit->interest_amount, 2) }}</span></div>
                                <div><span class="mobile-field-label">Total</span><span class="mobile-field-value">Q {{ number_format((float) $credit->total_amount, 2) }}</span></div>
                            </div>

                            <div class="mobile-card-actions">
                                <a class="btn" style="background:#eef2f7;" href="{{ route('credits.show', $credit) }}">Ver</a>
                            </div>
                        </article>
                    @empty
                        <p class="muted">No hay créditos registrados.</p>
                    @endforelse
                </div>

                <div style="margin-top:18px;">
                    {{ $credits->links() }}
                </div>
            </div>
        </section>
    </main>
</div>
@endsection
