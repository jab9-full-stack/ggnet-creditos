@extends('layouts.app')

@section('content')
<div class="app-shell">
    @include('partials.sidebar')

    <main class="main">
        <div class="page-header">
            <div>
                <h1>Caja</h1>
                <p class="page-subtitle">Apertura, cierre, arqueo y movimientos financieros. Los pagos anteriores a M05 quedan como históricos fuera de caja formal.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="panel" style="border-color:rgba(185,28,28,.35); background:#fff7f7;">
                <div class="panel-body">
                    @foreach ($errors->all() as $error)
                        <p style="margin:0 0 6px; color:var(--danger); font-weight:700;">{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        <section class="metric-grid">
            <div class="metric">
                <span>Estado</span>
                <strong>{{ $activeSession ? 'Caja abierta' : 'Sin caja abierta' }}</strong>
            </div>
            <div class="metric">
                <span>Saldo inicial</span>
                <strong>Q {{ $activeSession ? number_format((float) $activeSession->opening_balance, 2) : '0.00' }}</strong>
            </div>
            <div class="metric">
                <span>Saldo esperado</span>
                <strong>Q {{ $activeSession ? number_format($activeSession->expectedCashAmount(), 2) : '0.00' }}</strong>
            </div>
            <div class="metric">
                <span>Movimientos hoy</span>
                <strong>{{ $movements->count() }}</strong>
            </div>
        </section>

        <section class="panel">
            <div class="panel-body">
                @if (! $activeSession)
                    @can('cash.open')
                        <h2 style="margin:0 0 6px; font-size:18px;">Abrir caja</h2>
                        <p class="muted" style="margin:0 0 16px;">Cada usuario puede tener una caja abierta por agencia. La contabilidad agradece esta pequeña dosis de cordura.</p>

                        <form method="POST" action="{{ route('cash.open') }}" class="form-grid-uniform" data-confirm="true" data-confirm-title="Abrir caja" data-confirm-message="Se abrirá una nueva sesión de caja para tu usuario y agencia.">
                            @csrf

                            <label>
                                <span class="label">Saldo inicial <span style="color:var(--danger);">*</span></span>
                                <input class="input" name="opening_balance" type="number" min="0" step="0.01" value="{{ old('opening_balance', '0.00') }}" required>
                            </label>

                            <label>
                                <span class="label">Notas de apertura</span>
                                <input class="input" name="opening_notes" maxlength="1000" value="{{ old('opening_notes') }}" placeholder="Opcional">
                            </label>

                            <div style="display:flex; align-items:end;">
                                <button class="btn btn-primary" type="submit">Abrir caja</button>
                            </div>
                        </form>
                    @else
                        <p class="muted" style="margin:0;">No tienes permiso para abrir caja.</p>
                    @endcan
                @else
                    <div style="display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap;">
                        <div>
                            <h2 style="margin:0 0 6px; font-size:18px;">Caja actual {{ $activeSession->code }}</h2>
                            <p class="muted" style="margin:0;">
                                Abierta por {{ $activeSession->openedBy?->name ?? 'Sistema' }}
                                · {{ $activeSession->opened_at?->format('d/m/Y H:i') }}
                                · <span style="{{ $activeSession->statusStyle() }}">{{ $activeSession->statusLabel() }}</span>
                            </p>
                        </div>
                        <div style="text-align:right;">
                            <div class="muted">Saldo esperado</div>
                            <strong style="font-size:22px;">Q {{ number_format($activeSession->expectedCashAmount(), 2) }}</strong>
                        </div>
                    </div>

                    <div style="height:1px; background:#e5e7eb; margin:18px 0;"></div>

                    <div class="form-grid-uniform">
                        @can('cash_movements.create')
                            <form method="POST" action="{{ route('cash.movements.store', $activeSession) }}" data-confirm="true" data-confirm-title="Registrar movimiento" data-confirm-message="Se registrará un movimiento manual en la caja abierta.">
                                @csrf

                                <label>
                                    <span class="label">Tipo de movimiento <span style="color:var(--danger);">*</span></span>
                                    <select class="input" name="type" required>
                                        @foreach (\App\Models\CashMovement::MANUAL_TYPES as $value => $label)
                                            <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label>
                                    <span class="label">Monto <span style="color:var(--danger);">*</span></span>
                                    <input class="input" name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount') }}" required>
                                </label>

                                <label>
                                    <span class="label">Descripción <span style="color:var(--danger);">*</span></span>
                                    <input class="input" name="description" minlength="5" maxlength="1000" value="{{ old('description') }}" placeholder="Motivo del movimiento" required>
                                </label>

                                <div style="display:flex; align-items:end;">
                                    <button class="btn btn-primary" type="submit">Registrar movimiento</button>
                                </div>
                            </form>
                        @endcan

                        @can('cash.close')
                            <form method="POST" action="{{ route('cash.close', $activeSession) }}" data-confirm="true" data-confirm-title="Cerrar caja" data-confirm-message="Se cerrará la caja actual con el saldo contado indicado.">
                                @csrf

                                <label>
                                    <span class="label">Saldo contado <span style="color:var(--danger);">*</span></span>
                                    <input class="input" name="counted_cash_amount" type="number" min="0" step="0.01" value="{{ old('counted_cash_amount', number_format($activeSession->expectedCashAmount(), 2, '.', '')) }}" required>
                                </label>

                                <label>
                                    <span class="label">Notas de cierre</span>
                                    <input class="input" name="closing_notes" maxlength="1000" value="{{ old('closing_notes') }}" placeholder="Opcional">
                                </label>

                                <div style="display:flex; align-items:end;">
                                    <button class="btn" style="background:#111827; color:#fff;" type="submit">Cerrar caja</button>
                                </div>
                            </form>
                        @endcan
                    </div>
                @endif
            </div>
        </section>

        <section class="panel">
            <div class="panel-body">
                <h2 style="margin:0 0 6px; font-size:18px;">Totales del día por método</h2>
                <p class="muted" style="margin:0 0 16px;">Solo movimientos activos. Los anulados quedan visibles por auditoría, no por decoración.</p>

                <div class="metric-grid">
                    @foreach (\App\Models\CashMovement::METHODS as $method => $label)
                        <div class="metric">
                            <span>{{ $label }}</span>
                            <strong>Q {{ number_format((float) ($totalsByMethod[$method] ?? 0), 2) }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-body">
                <h2 style="margin:0 0 6px; font-size:18px;">Movimientos del día</h2>
                <p class="muted" style="margin:0 0 16px;">Ingresos, egresos, ajustes y anulaciones del día.</p>

                <div class="desktop-table table-scroll">
                    <table class="table compact-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Hora</th>
                                <th>Tipo</th>
                                <th>Método</th>
                                <th>Monto</th>
                                <th>Estado</th>
                                <th>Descripción</th>
                                <th>Usuario</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($movements as $movement)
                                <tr>
                                    <td><strong>{{ $movement->code }}</strong></td>
                                    <td>{{ $movement->movement_at?->format('d/m/Y H:i') }}</td>
                                    <td>{{ $movement->typeLabel() }}</td>
                                    <td>{{ $movement->methodLabel() }}</td>
                                    <td>
                                        <strong>
                                            {{ $movement->signedAmount() < 0 ? '-' : '' }}Q {{ number_format(abs($movement->signedAmount()), 2) }}
                                        </strong>
                                    </td>
                                    <td>
                                        <span style="{{ $movement->statusStyle() }}">{{ $movement->statusLabel() }}</span>
                                        @if ($movement->voided_at)
                                            <div class="muted">Anulado {{ $movement->voided_at?->format('d/m/Y H:i') }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $movement->description ?: '—' }}
                                        @if ($movement->void_reason)
                                            <div class="muted">Motivo: {{ $movement->void_reason }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $movement->createdBy?->name ?? 'Sistema' }}
                                        @if ($movement->voidedBy)
                                            <div class="muted">Anuló: {{ $movement->voidedBy?->name }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @can('cash_movements.void')
                                            @if ($movement->canBeVoided() && $movement->cash_session_id)
                                                <form method="POST" action="{{ route('cash.movements.void', [$movement->cash_session_id, $movement]) }}" data-confirm="true" data-confirm-title="Anular movimiento" data-confirm-message="Se anulará el movimiento. No se eliminará el registro.">
                                                    @csrf
                                                    <input class="input" name="void_reason" required minlength="5" maxlength="1000" placeholder="Motivo" style="min-width:180px; margin-bottom:8px;">
                                                    <button class="btn" style="background:#fff; border:1px solid var(--danger); color:var(--danger);" type="submit">Anular</button>
                                                </form>
                                            @else
                                                <span class="muted">—</span>
                                            @endif
                                        @else
                                            <span class="muted">—</span>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="muted">No hay movimientos registrados hoy.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mobile-list">
                    @forelse ($movements as $movement)
                        <article class="mobile-card">
                            <div class="mobile-card-title">{{ $movement->code }} · {{ $movement->typeLabel() }}</div>
                            <div class="mobile-card-subtitle">{{ $movement->movement_at?->format('d/m/Y H:i') }} · {{ $movement->methodLabel() }}</div>

                            <div class="mobile-fields">
                                <div>
                                    <span class="mobile-field-label">Monto</span>
                                    <span class="mobile-field-value">{{ $movement->signedAmount() < 0 ? '-' : '' }}Q {{ number_format(abs($movement->signedAmount()), 2) }}</span>
                                </div>
                                <div>
                                    <span class="mobile-field-label">Estado</span>
                                    <span class="mobile-field-value">{{ $movement->statusLabel() }}</span>
                                </div>
                                <div>
                                    <span class="mobile-field-label">Usuario</span>
                                    <span class="mobile-field-value">{{ $movement->createdBy?->name ?? 'Sistema' }}</span>
                                </div>
                            </div>

                            <p class="muted" style="margin-top:10px;">{{ $movement->description ?: 'Sin descripción' }}</p>

                            @if ($movement->void_reason)
                                <p class="muted" style="margin-top:8px;">Motivo anulación: {{ $movement->void_reason }}</p>
                            @endif
                        </article>
                    @empty
                        <p class="muted">No hay movimientos registrados hoy.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-body">
                <h2 style="margin:0 0 6px; font-size:18px;">Últimas sesiones de caja</h2>
                <p class="muted" style="margin:0 0 16px;">Historial básico de aperturas y cierres.</p>

                <div class="desktop-table table-scroll">
                    <table class="table compact-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Usuario</th>
                                <th>Agencia</th>
                                <th>Apertura</th>
                                <th>Cierre</th>
                                <th>Inicial</th>
                                <th>Esperado</th>
                                <th>Contado</th>
                                <th>Diferencia</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sessions as $session)
                                <tr>
                                    <td><strong>{{ $session->code }}</strong></td>
                                    <td>{{ $session->user?->name ?? '—' }}</td>
                                    <td>{{ $session->agency?->name ?? 'Sin agencia' }}</td>
                                    <td>{{ $session->opened_at?->format('d/m/Y H:i') }}</td>
                                    <td>{{ $session->closed_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td>Q {{ number_format((float) $session->opening_balance, 2) }}</td>
                                    <td>Q {{ number_format((float) $session->expected_cash_amount, 2) }}</td>
                                    <td>{{ $session->counted_cash_amount !== null ? 'Q '.number_format((float) $session->counted_cash_amount, 2) : '—' }}</td>
                                    <td>{{ $session->difference_amount !== null ? 'Q '.number_format((float) $session->difference_amount, 2) : '—' }}</td>
                                    <td><span style="{{ $session->statusStyle() }}">{{ $session->statusLabel() }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="muted">No hay sesiones de caja todavía.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</div>
@endsection
