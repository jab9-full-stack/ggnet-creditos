<section class="panel" style="margin-bottom:18px;">
    <div class="panel-body">
        <h2 style="margin:0 0 6px; font-size:18px;">Acciones de estado</h2>
        <p class="muted" style="margin:0 0 16px;">
            Estas acciones controlan el estado. Al aprobar se genera el crédito base automáticamente. No generan cuotas, pagos ni caja todavía.
        </p>

        @if (in_array($creditRequest->status, [
            \App\Models\CreditRequest::STATUS_DRAFT,
            \App\Models\CreditRequest::STATUS_SUBMITTED,
            \App\Models\CreditRequest::STATUS_IN_REVIEW,
        ], true))
            <div class="form-grid-uniform">
                @if ($creditRequest->status === \App\Models\CreditRequest::STATUS_DRAFT)
                    @can('credit_requests.update')
                        <form method="POST" action="{{ route('credit-requests.submit', $creditRequest) }}" data-confirm="true" data-confirm-title="Enviar solicitud" data-confirm-message="La solicitud pasará de borrador a enviada. Se conservará auditoría.">
                            @csrf
                            <button class="btn btn-primary" type="submit">Enviar solicitud</button>
                        </form>
                    @endcan
                @endif

                @if ($creditRequest->status === \App\Models\CreditRequest::STATUS_SUBMITTED)
                    @can('credit_requests.review')
                        <form method="POST" action="{{ route('credit-requests.start-review', $creditRequest) }}" data-confirm="true" data-confirm-title="Iniciar revisión" data-confirm-message="La solicitud pasará a revisión. Se conservará auditoría.">
                            @csrf
                            <button class="btn btn-primary" type="submit">Iniciar revisión</button>
                        </form>
                    @endcan
                @endif

                @if ($creditRequest->status === \App\Models\CreditRequest::STATUS_IN_REVIEW)
                    @can('credit_requests.approve')
                        <form method="POST" action="{{ route('credit-requests.approve', $creditRequest) }}" data-confirm="true" data-confirm-title="Aprobar solicitud" data-confirm-message="La solicitud quedará aprobada y se generará automáticamente el crédito base. No se crearán cuotas ni pagos todavía.">
                            @csrf
                            <button class="btn btn-primary" type="submit">Aprobar solicitud</button>
                        </form>
                    @endcan

                    @can('credit_requests.reject')
                        <form method="POST" action="{{ route('credit-requests.reject', $creditRequest) }}" data-confirm="true" data-confirm-title="Rechazar solicitud" data-confirm-message="La solicitud quedará rechazada. Esta acción se registrará en auditoría.">
                            @csrf
                            <button class="btn" style="background:#fee2e2; color:var(--danger);" type="submit">Rechazar solicitud</button>
                        </form>
                    @endcan
                @endif

                @can('credit_requests.update')
                    <form method="POST" action="{{ route('credit-requests.cancel', $creditRequest) }}" data-confirm="true" data-confirm-title="Cancelar solicitud" data-confirm-message="La solicitud quedará cancelada. Esta acción se registrará en auditoría.">
                        @csrf
                        <button class="btn" style="background:#eef2f7;" type="submit">Cancelar solicitud</button>
                    </form>
                @endcan
            </div>
        @else
            <div style="padding:14px; border:1px solid var(--line); border-radius:16px; background:#f9fafb;">
                <strong>Estado final</strong>
                <div class="muted" style="margin-top:4px;">
                    @if ($creditRequest->status === \App\Models\CreditRequest::STATUS_APPROVED && $creditRequest->credit)
                        Solicitud aprobada. Crédito {{ $creditRequest->credit->code }} generado automáticamente. Pendiente de entrega del dinero.
                    @else
                        Solicitud {{ mb_strtolower($creditRequest->statusLabel()) }}. Pendiente de generar crédito en módulo posterior.
                    @endif
                </div>
            </div>
        @endif
    </div>
</section>
