<section class="panel">
    <div class="panel-body">
        @if (isset($errors) && $errors->any())
            <div class="inline-error-source" data-toast-type="error" data-toast-title="Revisa la información" data-toast-message="{{ $errors->first() }}"></div>
        @endif

        <form method="POST" action="{{ $action }}">
            @csrf
            @if ($method !== 'POST')
                @method($method)
            @endif

            <h2 style="margin:0 0 6px; font-size:18px;">Datos principales</h2>
            <p class="muted" style="margin:0 0 12px;">Esto registra intención de crédito, no crédito aprobado.</p>

            <div class="form-grid-uniform">
                <label class="form-group span-2">
                    <span class="label">Cliente <span style="color:var(--danger);">*</span></span>
                    <select class="input" name="client_id" required>
                        <option value="">Seleccionar cliente</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}" @selected((string) old('client_id', $creditRequest->client_id) === (string) $client->id)>
                                {{ $client->code }} · {{ $client->fullName() }} · DPI {{ $client->dpi }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="form-group">
                    <span class="label">Agencia</span>
                    <select class="input" name="agency_id">
                        <option value="">Usar agencia del cliente</option>
                        @foreach ($agencies as $agency)
                            <option value="{{ $agency->id }}" @selected((string) old('agency_id', $creditRequest->agency_id) === (string) $agency->id)>
                                {{ $agency->code }} · {{ $agency->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="form-group">
                    <span class="label">Monto solicitado <span style="color:var(--danger);">*</span></span>
                    <input class="input" type="number" name="requested_amount" value="{{ old('requested_amount', $creditRequest->requested_amount) }}" step="0.01" min="1" required>
                </label>

                <label class="form-group">
                    <span class="label">Plazo solicitado en semanas</span>
                    <input class="input only-numbers" type="number" name="requested_term_weeks" value="{{ old('requested_term_weeks', $creditRequest->requested_term_weeks) }}" inputmode="numeric" min="1" max="104">
                </label>

                <label class="form-group">
                    <span class="label">Ingreso mensual aproximado</span>
                    <input class="input" type="number" name="monthly_income" value="{{ old('monthly_income', $creditRequest->monthly_income) }}" step="0.01" min="0">
                </label>

                <label class="form-group">
                    <span class="label">Fuente de ingresos</span>
                    <input class="input" name="income_source" value="{{ old('income_source', $creditRequest->income_source) }}" placeholder="Ej. Negocio propio, empleo, ventas...">
                </label>

                <label class="form-group span-3">
                    <span class="label">Destino / propósito</span>
                    <textarea class="input" name="purpose" rows="4">{{ old('purpose', $creditRequest->purpose) }}</textarea>
                </label>

                <label class="form-group span-3">
                    <span class="label">Observaciones</span>
                    <textarea class="input" name="notes" rows="4">{{ old('notes', $creditRequest->notes) }}</textarea>
                </label>
            </div>

            <p class="muted" style="margin-top:18px; font-size:13px;">
                Los campos marcados con <span style="color:var(--danger); font-weight:800;">*</span> son obligatorios. No se calculan cuotas en este módulo.
            </p>

            <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
                <a class="btn" style="background:#eef2f7;" href="{{ route('credit-requests.index') }}">Cancelar</a>
                <button class="btn btn-primary" type="submit">{{ $buttonText }}</button>
            </div>
        </form>
    </div>
</section>
