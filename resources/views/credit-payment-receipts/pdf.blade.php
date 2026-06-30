<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $receipt->code }}</title>

    <style>
        @page {
            margin: 30px 34px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: "DejaVu Sans", sans-serif;
            color: #111827;
            font-size: 11px;
            line-height: 1.45;
            margin: 0;
        }

        .document {
            width: 100%;
            position: relative;
        }

        .watermark {
            position: fixed;
            top: 310px;
            left: 55px;
            right: 55px;
            text-align: center;
            font-size: 72px;
            font-weight: bold;
            color: rgba(185, 28, 28, .11);
            transform: rotate(-22deg);
            z-index: -1;
        }

        .top-line {
            height: 5px;
            background: #0f766e;
            margin-bottom: 18px;
        }

        .header {
            width: 100%;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 14px;
            margin-bottom: 14px;
        }

        .brand {
            width: 62%;
            float: left;
        }

        .brand h1 {
            margin: 0;
            font-size: 20px;
            letter-spacing: -.4px;
            color: #064e3b;
        }

        .brand p {
            margin: 3px 0 0;
            color: #4b5563;
        }

        .receipt-meta {
            width: 36%;
            float: right;
            text-align: right;
        }

        .receipt-meta .label {
            color: #6b7280;
            text-transform: uppercase;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: .6px;
        }

        .receipt-meta .code {
            font-size: 22px;
            font-weight: bold;
            color: #111827;
            margin-top: 2px;
        }

        .status {
            display: inline-block;
            margin-top: 6px;
            padding: 4px 8px;
            border-radius: 999px;
            border: 1px solid #d1d5db;
            font-weight: bold;
            font-size: 10px;
        }

        .status.active {
            color: #065f46;
            border-color: #a7f3d0;
            background: #ecfdf5;
        }

        .status.voided {
            color: #991b1b;
            border-color: #fecaca;
            background: #fef2f2;
        }

        .clear {
            clear: both;
        }

        .title-box {
            border: 1px solid #d1d5db;
            background: #f9fafb;
            padding: 12px;
            margin-bottom: 14px;
        }

        .title-box h2 {
            margin: 0;
            font-size: 16px;
            text-align: center;
            letter-spacing: .4px;
        }

        .title-box p {
            margin: 5px 0 0;
            text-align: center;
            color: #4b5563;
        }

        .void-box {
            border: 1px solid #ef4444;
            background: #fef2f2;
            color: #7f1d1d;
            padding: 10px 12px;
            margin-bottom: 14px;
            font-weight: bold;
        }

        .section-title {
            margin: 15px 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e5e7eb;
            color: #064e3b;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .info-table td {
            width: 33.33%;
            border: 1px solid #e5e7eb;
            padding: 8px;
            vertical-align: top;
        }

        .field-label {
            display: block;
            color: #6b7280;
            text-transform: uppercase;
            font-size: 8.5px;
            font-weight: bold;
            letter-spacing: .5px;
            margin-bottom: 3px;
        }

        .field-value {
            display: block;
            font-weight: bold;
            color: #111827;
            word-wrap: break-word;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .detail-table th {
            background: #0f766e;
            color: white;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .4px;
            padding: 7px 6px;
        }

        .detail-table td {
            border: 1px solid #e5e7eb;
            padding: 7px 6px;
        }

        .text-right {
            text-align: right;
        }

        .total-table {
            width: 100%;
            margin-top: 14px;
            border-collapse: collapse;
        }

        .total-table td {
            padding: 8px;
        }

        .total-label {
            text-align: right;
            color: #4b5563;
            font-size: 12px;
            font-weight: bold;
        }

        .total-value {
            width: 190px;
            text-align: right;
            background: #064e3b;
            color: white;
            font-size: 18px;
            font-weight: bold;
            border-radius: 4px;
        }

        .note {
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            padding: 9px;
            margin-top: 12px;
            color: #374151;
        }

        .signatures {
            width: 100%;
            margin-top: 44px;
        }

        .signatures td {
            width: 50%;
            text-align: center;
            padding: 0 28px;
        }

        .signature-line {
            border-top: 1px solid #111827;
            padding-top: 7px;
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: -12px;
            left: 0;
            right: 0;
            border-top: 1px solid #d1d5db;
            padding-top: 6px;
            color: #6b7280;
            font-size: 8.5px;
        }

        .footer-left {
            float: left;
            width: 60%;
        }

        .footer-right {
            float: right;
            width: 38%;
            text-align: right;
        }
    </style>
</head>
<body>
    @if ($receipt->status === \App\Models\CreditPaymentReceipt::STATUS_VOIDED)
        <div class="watermark">ANULADO</div>
    @endif

    <div class="document">
        <div class="top-line"></div>

        <div class="header">
            <div class="brand">
                <h1>BIENESTAR</h1>
                <p>{{ $receipt->agency?->name ?? 'Agencia Central' }}</p>
                <p>Comprobante financiero de pago de crédito</p>
            </div>

            <div class="receipt-meta">
                <div class="label">Recibo No.</div>
                <div class="code">{{ $receipt->code }}</div>
                <div class="status {{ $receipt->status }}">
                    {{ mb_strtoupper($receipt->statusLabel()) }}
                </div>
            </div>

            <div class="clear"></div>
        </div>

        <div class="title-box">
            <h2>COMPROBANTE DE PAGO DE CRÉDITO</h2>
            <p>Documento generado por el sistema BIENESTAR para control interno, caja y auditoría.</p>
        </div>

        @if ($receipt->status === \App\Models\CreditPaymentReceipt::STATUS_VOIDED)
            <div class="void-box">
                RECIBO ANULADO
                @if ($receipt->voided_at)
                    · {{ $receipt->voided_at?->format('d/m/Y H:i') }}
                @endif
                @if ($receipt->voidedBy)
                    · Anulado por {{ $receipt->voidedBy?->name }}
                @endif
                @if ($receipt->void_reason)
                    <br>Motivo: {{ $receipt->void_reason }}
                @endif
            </div>
        @endif

        <div class="section-title">Datos del cliente y crédito</div>

        <table class="info-table">
            <tr>
                <td>
                    <span class="field-label">Cliente</span>
                    <span class="field-value">{{ $receipt->client?->fullName() ?? '—' }}</span>
                </td>
                <td>
                    <span class="field-label">Código cliente</span>
                    <span class="field-value">{{ $receipt->client?->code ?? '—' }}</span>
                </td>
                <td>
                    <span class="field-label">DPI</span>
                    <span class="field-value">{{ $receipt->client?->dpi ?? '—' }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="field-label">Crédito</span>
                    <span class="field-value">{{ $receipt->credit?->code ?? '—' }}</span>
                </td>
                <td>
                    <span class="field-label">Pago</span>
                    <span class="field-value">{{ $receipt->payment?->code ?? '—' }}</span>
                </td>
                <td>
                    <span class="field-label">Estado del pago</span>
                    <span class="field-value">{{ $receipt->payment?->statusLabel() ?? '—' }}</span>
                </td>
            </tr>
        </table>

        <div class="section-title">Datos de recepción</div>

        <table class="info-table">
            <tr>
                <td>
                    <span class="field-label">Fecha y hora</span>
                    <span class="field-value">{{ $receipt->issued_at?->format('d/m/Y H:i') ?? '—' }}</span>
                </td>
                <td>
                    <span class="field-label">Método</span>
                    <span class="field-value">{{ $receipt->methodLabel() }}</span>
                </td>
                <td>
                    <span class="field-label">Referencia</span>
                    <span class="field-value">{{ $receipt->reference ?: 'No aplica' }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="field-label">Recibido por</span>
                    <span class="field-value">{{ $receipt->issuedBy?->name ?? 'Sistema' }}</span>
                </td>
                <td>
                    <span class="field-label">Caja</span>
                    <span class="field-value">{{ $receipt->cashSession?->code ?? 'No aplica' }}</span>
                </td>
                <td>
                    <span class="field-label">Movimiento financiero</span>
                    <span class="field-value">{{ $receipt->cashMovement?->code ?? '—' }}</span>
                </td>
            </tr>
        </table>

        <div class="section-title">Detalle de cuotas pagadas</div>

        <table class="detail-table">
            <thead>
                <tr>
                    <th style="width: 9%;">No.</th>
                    <th style="width: 23%;">Vencimiento</th>
                    <th class="text-right" style="width: 22%;">Capital</th>
                    <th class="text-right" style="width: 22%;">Interés</th>
                    <th class="text-right" style="width: 24%;">Total cuota</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($receipt->installments_snapshot ?? []) as $item)
                    <tr>
                        <td><strong>{{ $item['number'] ?? '—' }}</strong></td>
                        <td>
                            {{ isset($item['due_date']) && $item['due_date'] ? \Illuminate\Support\Carbon::parse($item['due_date'])->format('d/m/Y') : '—' }}
                        </td>
                        <td class="text-right">Q {{ number_format((float) ($item['principal_amount'] ?? 0), 2) }}</td>
                        <td class="text-right">Q {{ number_format((float) ($item['interest_amount'] ?? 0), 2) }}</td>
                        <td class="text-right"><strong>Q {{ number_format((float) ($item['total_amount'] ?? 0), 2) }}</strong></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No hay cuotas registradas en el recibo.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="total-table">
            <tr>
                <td class="total-label">TOTAL RECIBIDO</td>
                <td class="total-value">Q {{ number_format((float) $receipt->amount, 2) }}</td>
            </tr>
        </table>

        @if ($receipt->payment?->notes)
            <div class="note">
                <strong>Nota interna:</strong><br>
                {{ $receipt->payment?->notes }}
            </div>
        @endif

        <table class="signatures">
            <tr>
                <td>
                    <div class="signature-line">Firma del cliente</div>
                </td>
                <td>
                    <div class="signature-line">Firma del cajero / receptor</div>
                </td>
            </tr>
        </table>

        <div class="footer">
            <div class="footer-left">
                Recibo {{ $receipt->code }} · Pago {{ $receipt->payment?->code ?? '—' }} · Crédito {{ $receipt->credit?->code ?? '—' }}
            </div>
            <div class="footer-right">
                Generado {{ $generatedAt?->format('d/m/Y H:i:s') }}
            </div>
            <div class="clear"></div>
        </div>
    </div>
</body>
</html>
