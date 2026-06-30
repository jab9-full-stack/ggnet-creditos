@extends('layouts.app')

@section('content')
<div class="app-shell">
    @include('partials.sidebar')

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-title">Configuración de mora</h1>
                <p class="page-subtitle">Define cómo se aplicarán los recargos por cuotas vencidas. La mora no se duplica sobre una misma cuota.</p>
            </div>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a class="btn" style="background:#eef2f7;" href="{{ route('settings.index', ['group' => 'credits']) }}">Ver parámetros técnicos</a>
            </div>
        </header>

        @if (session('status'))
            <div hidden data-toast-type="success" data-toast-title="Operación completada" data-toast-message="{{ session('status') }}"></div>
        @endif

        @if (session('error'))
            <div hidden data-toast-type="error" data-toast-title="No se pudo completar" data-toast-message="{{ session('error') }}"></div>
        @endif

        @if (isset($errors) && $errors->any())
            <div hidden data-toast-type="error" data-toast-title="Revisa la configuración" data-toast-message="{{ $errors->first() }}"></div>
        @endif

        @php
            $enabled = old('enabled', $configuration['enabled'] ? '1' : null);
            $type = old('type', $configuration['type']);
            $fixedAmount = old('fixed_amount', number_format((float) $configuration['fixed_amount'], 2, '.', ''));
            $percentage = old('percentage', number_format((float) $configuration['percentage'], 4, '.', ''));
            $graceDays = old('grace_days', (string) $configuration['grace_days']);
        @endphp

        <section class="panel" style="margin-bottom:18px;">
            <div class="panel-body">
                <div class="metric-grid">
                    <div class="metric">
                        <span>Estado</span>
                        <strong style="{{ $configuration['enabled'] ? 'color:var(--success);' : 'color:var(--danger);' }}">
                            {{ $configuration['enabled'] ? 'Activa' : 'Desactivada' }}
                        </strong>
                    </div>

                    <div class="metric">
                        <span>Tipo</span>
                        <strong>{{ $configuration['type'] === 'percentage' ? 'Porcentaje' : 'Monto fijo' }}</strong>
                    </div>

                    <div class="metric">
                        <span>Monto fijo</span>
                        <strong>Q {{ number_format((float) $configuration['fixed_amount'], 2) }}</strong>
                    </div>

                    <div class="metric">
                        <span>Porcentaje</span>
                        <strong>{{ number_format((float) $configuration['percentage'], 4) }}%</strong>
                    </div>

                    <div class="metric">
                        <span>Días de gracia</span>
                        <strong>{{ (int) $configuration['grace_days'] }}</strong>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-body">
                <form method="POST" action="{{ route('settings.credit-late-fees.update') }}" style="display:grid; gap:18px;">
                    @csrf
                    @method('PUT')

                    <div style="border:1px solid var(--line); border-radius:18px; padding:16px; background:#f9fafb;">
                        <label style="display:flex; align-items:flex-start; gap:12px; margin:0;">
                            <input type="checkbox" name="enabled" value="1" @checked($enabled) style="margin-top:4px;">
                            <span>
                                <strong>Activar mora</strong>
                                <span class="muted" style="display:block; margin-top:4px;">
                                    Si está desactivada, el proceso seguirá marcando cuotas vencidas, pero no agregará recargos.
                                </span>
                            </span>
                        </label>
                    </div>

                    <div class="form-grid">
                        <label class="form-group">
                            <span class="label">Tipo de mora <span style="color:var(--danger); font-weight:800;">*</span></span>
                            <select class="input" name="type" required>
                                @foreach ($types as $value => $label)
                                    <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <span class="muted">Monto fijo aplica una cantidad exacta por cuota. Porcentaje calcula sobre capital + interés de la cuota.</span>
                        </label>

                        <label class="form-group">
                            <span class="label">Monto fijo por cuota vencida</span>
                            <input class="input" name="fixed_amount" type="number" min="0" max="99999.99" step="0.01" value="{{ $fixedAmount }}">
                            <span class="muted">Ejemplo: 25.00 significa Q25.00 por cuota vencida.</span>
                        </label>

                        <label class="form-group">
                            <span class="label">Porcentaje de mora</span>
                            <input class="input" name="percentage" type="number" min="0" max="100" step="0.0001" value="{{ $percentage }}">
                            <span class="muted">Ejemplo: 5.0000 significa 5% sobre capital + interés de la cuota.</span>
                        </label>

                        <label class="form-group">
                            <span class="label">Días de gracia <span style="color:var(--danger); font-weight:800;">*</span></span>
                            <input class="input" name="grace_days" type="number" min="0" max="30" step="1" value="{{ $graceDays }}" required>
                            <span class="muted">Con 0, la mora puede aplicar desde el primer día posterior al vencimiento.</span>
                        </label>
                    </div>

                    <div style="border:1px solid #fed7aa; background:#fff7ed; border-radius:18px; padding:16px; color:#7c2d12;">
                        <strong>Regla operativa:</strong>
                        la mora se aplica una sola vez por cuota vencida. El sistema no recalcula ni duplica recargos ya aplicados.
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap;">
                        <a class="btn" style="background:#eef2f7;" href="{{ route('settings.index', ['group' => 'credits']) }}">Cancelar</a>
                        @can('settings.update')
                            <button class="btn btn-primary" type="submit">Guardar configuración</button>
                        @else
                            <span class="muted">No tienes permiso para modificar configuración.</span>
                        @endcan
                    </div>
                </form>
            </div>
        </section>
    </main>
</div>
@endsection
