@extends('layouts.app')

@section('content')
<div class="app-shell receipt-page">
    @include('partials.sidebar')

    <main class="main">
        <header class="topbar no-print">
            <div>
                <h1 class="page-title">Recibo de pago</h1>
                <p class="page-subtitle">{{ $receipt->code }} · Pago {{ $payment->code }}</p>
            </div>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a class="btn" style="background:#ffffff; border:1px solid var(--line);" href="{{ route('credits.show', $credit) }}">Volver al crédito</a>
                <button class="btn btn-primary" type="button" onclick="window.print()">Imprimir</button>
            </div>
        </header>

        <style>
            .receipt-box {
                max-width: 860px;
                margin: 0 auto;
                background: #fff;
                border: 1px solid var(--line);
                border-radius: 22px;
                box-shadow: 0 10px 32px rgba(15,23,42,.045);
                padding: 28px;
            }

            .receipt-head {
                display: flex;
                justify-content: space-between;
                gap: 18px;
                align-items: flex-start;
                border-bottom: 1px solid var(--line);
                padding-bottom: 18px;
                margin-bottom: 18px;
            }

            .receipt-title {
                margin: 0;
                font-size: 26px;
                letter-spacing: -.04em;
            }

            .receipt-code {
                font-size: 24px;
                font-weight: 900;
                letter-spacing: -.03em;
                text-align: right;
            }

            .receipt-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 12px;
                margin: 18px 0;
            }

            .receipt-field {
                border: 1px solid var(--line);
                border-radius: 14px;
                padding: 12px;
                background: #fff;
            }

            .receipt-field span {
                display: block;
                color: var(--muted);
                font-size: 12px;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: .05em;
            }

            .receipt-field strong {
                display: block;
                margin-top: 6px;
                font-size: 15px;
            }

            .receipt-total {
                text-align: right;
                border-top: 1px solid var(--line);
                padding-top: 18px;
                margin-top: 18px;
            }

            .receipt-total span {
                color: var(--muted);
                font-weight: 800;
            }

            .receipt-total strong {
                display: block;
                font-size: 32px;
                letter-spacing: -.04em;
                margin-top: 4px;
            }

            .void-banner {
                border: 1px solid rgba(185,28,28,.28);
                background: #fff7f7;
                color: #7f1d1d;
                border-radius: 16px;
                padding: 14px;
                margin-bottom: 18px;
                font-weight: 800;
            }

            @media (max-width: 900px) {
                .receipt-grid {
                    grid-template-columns: 1fr;
                }

                .receipt-head {
                    display: grid;
                }

                .receipt-code {
                    text-align: left;
                }
            }

            @media print {
                body {
                    background: #fff !important;
                }

                .sidebar,
                .no-print,
                .page-loader,
                .toast-stack,
                .confirm-backdrop {
                    display: none !important;
                }

                .app-shell {
                    display: block !important;
                }

                .main {
                    padding: 0 !important;
                    overflow: visible !important;
                }

                .receipt-box {
                    max-width: none;
                    border: 0;
                    box-shadow: none;
                    border-radius: 0;
                    padding: 0;
                }

                .table th,
                .table td {
                    padding: 8px 6px;
                    font-size: 12px;
                }
            }
        </style>

        <section class="receipt-box">
            @if ($receipt->status === \App\Models\CreditPaymentReceipt::STATUS_VOIDED)
                <div class="void-banner">
                    RECIBO ANULADO
                    @if ($receipt->voided_at)
                        · {{ $receipt->voided_at?->format('d/m/Y H:i') }}
                    @endif
                    @if ($receipt->void_reason)
                        <div style="font-weight:700; margin-top:6px;">Motivo: {{ $receipt->void_reason }}</div>
                    @endif
                </div>
            @endif

            <div class="receipt-head">
                <div>
                    <h2 class="receipt-title">{{ $receipt->agency?->legal_name ?: ($receipt->agency?->name ?: 'GGNET Créditos') }}</h2>
                    <p class="muted" style="margin:6px 0 0;">Comprobante interno de pago de crédito</p>
                    <p class="muted" style="margin:4px 0 0;">{{ $receipt->agency?->name ?? 'Agencia no registrada' }}</p>
                </div>

                <div>
                    <div class="receipt-code">{{ $receipt->code }}</div>
                    <div style="{{ $receipt->statusStyle() }}">{{ $receipt->statusLabel() }}</div>
                </div>
            </div>

            <div class="receipt-grid">
                <div class="receipt-field">
                    <span>Cliente</span>
                    <strong>{{ $receipt->client?->fullName() ?? '—' }}</strong>
                </div>

                <div class="receipt-field">
                    <span>DPI</span>
                    <strong>{{ $receipt->client?->dpi ?? '—' }}</strong>
                </div>

                <div class="receipt-field">
                    <span>Crédito</span>
                    <strong>{{ $receipt->credit?->code ?? '—' }}</strong>
                </div>

                <div class="receipt-field">
                    <span>Pago</span>
                    <strong>{{ $receipt->payment?->code ?? '—' }}</strong>
                </div>

                <div class="receipt-field">
                    <span>Fecha y hora</span>
                    <strong>{{ $receipt->issued_at?->format('d/m/Y H:i') ?? '—' }}</strong>
                </div>

                <div class="receipt-field">
                    <span>Método</span>
                    <strong>{{ $receipt->methodLabel() }}</strong>
                </div>

                <div class="receipt-field">
                    <span>Referencia</span>
                    <strong>{{ $receipt->reference ?: '—' }}</strong>
                </div>

                <div class="receipt-field">
                    <span>Cajero / Usuario</span>
                    <strong>{{ $receipt->issuedBy?->name ?? 'Sistema' }}</strong>
                </div>

                <div class="receipt-field">
                    <span>Caja</span>
                    <strong>{{ $receipt->cashSession?->code ?? 'No aplica' }}</strong>
                </div>

                <div class="receipt-field">
                    <span>Movimiento</span>
                    <strong>{{ $receipt->cashMovement?->code ?? '—' }}</strong>
                </div>

                <div class="receipt-field">
                    <span>Cuotas pagadas</span>
                    <strong>{{ $receipt->installments_count }}</strong>
                </div>

                <div class="receipt-field">
                    <span>Estado del pago</span>
                    <strong>{{ $receipt->payment?->statusLabel() ?? '—' }}</strong>
                </div>
            </div>

            <h3 style="margin:22px 0 10px;">Detalle de cuotas</h3>

            <div class="table-scroll">
                <table class="table compact-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Vencimiento</th>
                            <th>Capital</th>
                            <th>Interés</th>
                            <th>Total cuota</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($receipt->installments_snapshot ?? []) as $item)
                            <tr>
                                <td><strong>{{ $item['number'] ?? '—' }}</strong></td>
                                <td>{{ isset($item['due_date']) && $item['due_date'] ? \Illuminate\Support\Carbon::parse($item['due_date'])->format('d/m/Y') : '—' }}</td>
                                <td>Q {{ number_format((float) ($item['principal_amount'] ?? 0), 2) }}</td>
                                <td>Q {{ number_format((float) ($item['interest_amount'] ?? 0), 2) }}</td>
                                <td><strong>Q {{ number_format((float) ($item['total_amount'] ?? 0), 2) }}</strong></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="muted">No hay cuotas registradas en el recibo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="receipt-total">
                <span>Total recibido</span>
                <strong>Q {{ number_format((float) $receipt->amount, 2) }}</strong>
            </div>

            @if ($receipt->payment?->notes)
                <div style="margin-top:18px; border-top:1px solid var(--line); padding-top:14px;">
                    <strong>Nota interna:</strong>
                    <p class="muted" style="margin:6px 0 0;">{{ $receipt->payment?->notes }}</p>
                </div>
            @endif

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:28px; margin-top:46px;">
                <div style="border-top:1px solid #111827; padding-top:8px; text-align:center;">Firma cliente</div>
                <div style="border-top:1px solid #111827; padding-top:8px; text-align:center;">Firma cajero</div>
            </div>
        </section>
    </main>
</div>
@endsection
