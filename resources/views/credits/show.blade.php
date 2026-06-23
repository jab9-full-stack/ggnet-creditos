@extends('layouts.app')

@section('content')
<div class="app-shell">
    @include('partials.sidebar')

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-title">Detalle de crédito</h1>
                <p class="page-subtitle">{{ $credit->code }} · {{ $credit->client?->fullName() }}</p>
            </div>

            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <a class="btn" style="background:#ffffff; border:1px solid var(--line); color:#111827; box-shadow:0 4px 12px rgba(15,23,42,.06);" href="{{ route('credits.index') }}">Volver</a>

                @can('credit_requests.view')
                    <a class="btn" style="background:#ffffff; border:1px solid var(--line); color:#111827; box-shadow:0 4px 12px rgba(15,23,42,.06);" href="{{ route('credit-requests.show', $credit->creditRequest) }}">Ver solicitud</a>
                @endcan

                @can('clients.view')
                    <a class="btn" style="background:#ffffff; border:1px solid var(--line); color:#111827; box-shadow:0 4px 12px rgba(15,23,42,.06);" href="{{ route('clients.show', $credit->client) }}">Ver expediente</a>
                @endcan
            </div>
        </header>

        @if (session('status'))
            <div hidden data-toast-type="success" data-toast-title="Operación completada" data-toast-message="{{ session('status') }}"></div>
        @endif

        @if (session('error'))
            <div hidden data-toast-type="error" data-toast-title="No se pudo completar" data-toast-message="{{ session('error') }}"></div>
        @endif

        @if ($errors->any())
            <div hidden data-toast-type="error" data-toast-title="Revisa la información" data-toast-message="{{ $errors->first() }}"></div>
        @endif

        <section class="grid grid-3" style="margin-bottom:18px;">
            <div class="metric">
                <span>Capital</span>
                <strong>Q {{ number_format((float) $credit->principal_amount, 2) }}</strong>
            </div>

            <div class="metric">
                <span>Interés</span>
                <strong>{{ number_format((float) $credit->interest_rate_percent, 2) }}%</strong>
            </div>

            <div class="metric">
                <span>Total a recuperar</span>
                <strong>Q {{ number_format((float) $credit->total_amount, 2) }}</strong>
            </div>
        </section>

        @can('credits.disburse')
            @if ($credit->canBeDisbursed())
                <section class="panel" style="margin-bottom:18px; border:1px solid rgba(13,148,136,.28);">
                    <div class="panel-body">
                        <h2 style="margin:0 0 6px; font-size:18px;">Confirmar entrega del dinero</h2>
                        <p class="muted" style="margin:0 0 16px;">Al confirmar la entrega, el crédito pasará a entregado y se generarán 4 cuotas semanales. No se registra pago ni movimiento de caja todavía.</p>

                        <form method="POST" action="{{ route('credits.disburse', $credit) }}" data-confirm="true" data-confirm-title="Confirmar entrega del dinero" data-confirm-message="Se marcará el crédito como entregado y se generará el calendario de 4 cuotas semanales. Esta acción no registra pagos.">
                            @csrf

                            <div class="form-grid-uniform">
                                <label class="form-group">
                                    <span class="label">Fecha real de entrega <span style="color:var(--danger);">*</span></span>
                                    <input
                                        class="input"
                                        type="date"
                                        name="disbursement_date"
                                        value="{{ old('disbursement_date', now()->toDateString()) }}"
                                        min="{{ $credit->approved_at?->toDateString() }}"
                                        max="{{ now()->toDateString() }}"
                                        required
                                    >
                                </label>

                                <div style="display:flex; align-items:end;">
                                    <button class="btn btn-primary" type="submit">Confirmar entrega y generar cuotas</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </section>
            @endif
        @endcan

        <section class="panel" style="margin-bottom:18px;">
            <div class="panel-body">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:14px; margin-bottom:16px; flex-wrap:wrap;">
                    <div>
                        <h2 style="margin:0 0 6px; font-size:18px;">Crédito</h2>
                        <p class="muted" style="margin:0;">
                            @if ($credit->status === \App\Models\Credit::STATUS_DISBURSED)
                                Crédito entregado. Calendario generado. Los pagos se trabajarán en módulo posterior.
                            @else
                                Crédito aprobado pendiente de entrega. Las cuotas se generarán al confirmar entrega del dinero.
                            @endif
                        </p>
                    </div>

                    <span style="display:inline-flex; align-items:center; padding:8px 12px; border-radius:999px; background:#f9fafb; border:1px solid var(--line); {{ $credit->statusStyle() }}">
                        {{ $credit->statusLabel() }}
                    </span>
                </div>

                <div class="form-grid-uniform">
                    <div style="padding:14px; border:1px solid var(--line); border-radius:16px; background:#fff;">
                        <span class="mobile-field-label">Código</span>
                        <span class="mobile-field-value" style="font-size:15px;">{{ $credit->code }}</span>
                    </div>

                    <div style="padding:14px; border:1px solid var(--line); border-radius:16px; background:#fff;">
                        <span class="mobile-field-label">Solicitud origen</span>
                        <span class="mobile-field-value" style="font-size:15px;">{{ $credit->creditRequest?->code }}</span>
                    </div>

                    <div style="padding:14px; border:1px solid var(--line); border-radius:16px; background:#fff;">
                        <span class="mobile-field-label">Agencia</span>
                        <span class="mobile-field-value" style="font-size:15px;">{{ $credit->agency?->name ?? 'Sin agencia' }}</span>
                    </div>

                    <div style="padding:14px; border:1px solid var(--line); border-radius:16px; background:#fff;">
                        <span class="mobile-field-label">Plazo</span>
                        <span class="mobile-field-value" style="font-size:15px;">{{ $credit->term_weeks }} semanas</span>
                    </div>

                    <div style="padding:14px; border:1px solid var(--line); border-radius:16px; background:#fff;">
                        <span class="mobile-field-label">Interés monto</span>
                        <span class="mobile-field-value" style="font-size:15px;">Q {{ number_format((float) $credit->interest_amount, 2) }}</span>
                    </div>

                    <div style="padding:14px; border:1px solid var(--line); border-radius:16px; background:#fff;">
                        <span class="mobile-field-label">Fecha de aprobación</span>
                        <span class="mobile-field-value" style="font-size:15px;">{{ $credit->approved_at?->format('d/m/Y H:i') ?? '—' }}</span>
                    </div>

                    <div style="padding:14px; border:1px solid var(--line); border-radius:16px; background:#fff;">
                        <span class="mobile-field-label">Fecha de entrega</span>
                        <span class="mobile-field-value" style="font-size:15px;">{{ $credit->disbursed_at?->format('d/m/Y H:i') ?? 'Pendiente' }}</span>
                    </div>

                    <div style="padding:14px; border:1px solid var(--line); border-radius:16px; background:#fff;">
                        <span class="mobile-field-label">Entregado por</span>
                        <span class="mobile-field-value" style="font-size:15px;">{{ $credit->disbursedBy?->name ?? 'Pendiente' }}</span>
                    </div>

                    <div class="span-3" style="padding:14px; border:1px solid var(--line); border-radius:16px; background:#fff;">
                        <span class="mobile-field-label">Nota</span>
                        <span class="mobile-field-value" style="font-size:15px; line-height:1.6;">{{ $credit->notes ?: 'Sin nota registrada' }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel" style="margin-bottom:18px;">
            <div class="panel-body">
                <h2 style="margin:0 0 6px; font-size:18px;">Calendario de cuotas</h2>
                <p class="muted" style="margin:0 0 16px;">Cuotas generadas a partir de la fecha real de entrega. Todavía no hay pagos aplicados.</p>

                <div class="desktop-table table-scroll">
                    <table class="table compact-table">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Fecha de vencimiento</th>
                                <th>Capital</th>
                                <th>Interés</th>
                                <th>Total cuota</th>
                                <th>Pagado</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($credit->installments as $installment)
                                <tr>
                                    <td><strong>{{ $installment->number }}</strong></td>
                                    <td>{{ $installment->due_date?->format('d/m/Y') }}</td>
                                    <td>Q {{ number_format((float) $installment->principal_amount, 2) }}</td>
                                    <td>Q {{ number_format((float) $installment->interest_amount, 2) }}</td>
                                    <td><strong>Q {{ number_format((float) $installment->total_amount, 2) }}</strong></td>
                                    <td>Q {{ number_format((float) $installment->paid_amount, 2) }}</td>
                                    <td><span style="{{ $installment->statusStyle() }}">{{ $installment->statusLabel() }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="muted">El calendario se generará al confirmar la entrega del dinero.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mobile-list">
                    @forelse ($credit->installments as $installment)
                        <article class="mobile-card">
                            <div class="mobile-card-title">Cuota {{ $installment->number }} · Q {{ number_format((float) $installment->total_amount, 2) }}</div>
                            <div class="mobile-card-subtitle">Vence {{ $installment->due_date?->format('d/m/Y') }}</div>

                            <div style="margin-top:10px;">
                                <span style="{{ $installment->statusStyle() }}">{{ $installment->statusLabel() }}</span>
                            </div>

                            <div class="mobile-card-grid">
                                <div><span class="mobile-field-label">Capital</span><span class="mobile-field-value">Q {{ number_format((float) $installment->principal_amount, 2) }}</span></div>
                                <div><span class="mobile-field-label">Interés</span><span class="mobile-field-value">Q {{ number_format((float) $installment->interest_amount, 2) }}</span></div>
                                <div><span class="mobile-field-label">Pagado</span><span class="mobile-field-value">Q {{ number_format((float) $installment->paid_amount, 2) }}</span></div>
                                <div><span class="mobile-field-label">Estado</span><span class="mobile-field-value">{{ $installment->statusLabel() }}</span></div>
                            </div>
                        </article>
                    @empty
                        <p class="muted">El calendario se generará al confirmar la entrega del dinero.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="panel" style="margin-bottom:18px;">
            <div class="panel-body">
                <h2 style="margin:0 0 6px; font-size:18px;">Cliente asociado</h2>
                <p class="muted" style="margin:0 0 16px;">Resumen del cliente vinculado al crédito.</p>

                <div class="form-grid-uniform">
                    <div>
                        <span class="mobile-field-label">Cliente</span>
                        <span class="mobile-field-value">{{ $credit->client?->fullName() }}</span>
                    </div>

                    <div>
                        <span class="mobile-field-label">DPI</span>
                        <span class="mobile-field-value">{{ $credit->client?->dpi }}</span>
                    </div>

                    <div>
                        <span class="mobile-field-label">Teléfono</span>
                        <span class="mobile-field-value">{{ $credit->client?->phone }}</span>
                    </div>

                    <div class="span-3">
                        <span class="mobile-field-label">Dirección</span>
                        <span class="mobile-field-value">{{ $credit->client?->address_line }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-body">
                <h2 style="margin:0 0 6px; font-size:18px;">Auditoría del crédito</h2>
                <p class="muted" style="margin:0 0 16px;">Eventos registrados sobre este crédito.</p>

                <div class="desktop-table table-scroll">
                    <table class="table compact-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Evento</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($auditLogs as $log)
                                <tr>
                                    <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                                    <td>{{ $log->event }}</td>
                                    <td>
                                        {{ $log->user?->name ?? 'Sistema' }}
                                        <div class="muted">{{ $log->user?->email }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="muted">Sin eventos registrados todavía.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mobile-list">
                    @forelse ($auditLogs as $log)
                        <article class="mobile-card">
                            <div class="mobile-card-title">{{ $log->event }}</div>
                            <div class="mobile-card-subtitle">{{ $log->created_at?->format('d/m/Y H:i') }} · {{ $log->user?->name ?? 'Sistema' }}</div>
                        </article>
                    @empty
                        <p class="muted">Sin eventos registrados todavía.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </main>
</div>
@endsection
