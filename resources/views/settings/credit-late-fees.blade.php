@extends('layouts.app')

@section('content')
<div class="app-shell">
    @include('partials.sidebar')

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-title">Política de atraso</h1>
                <p class="page-subtitle">La mora no cobra recargos. El atraso bloquea nuevos créditos para el cliente.</p>
            </div>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a class="btn" style="background:#eef2f7;" href="{{ route('settings.index', ['group' => 'credits']) }}">Ver parámetros técnicos</a>
            </div>
        </header>

        @if (session('status'))
            <div hidden data-toast-type="success" data-toast-title="Operación completada" data-toast-message="{{ session('status') }}"></div>
        @endif

        @if (isset($errors) && $errors->any())
            <div hidden data-toast-type="error" data-toast-title="Revisa la configuración" data-toast-message="{{ $errors->first() }}"></div>
        @endif

        <section class="panel" style="margin-bottom:18px;">
            <div class="panel-body">
                <div class="metric-grid">
                    <div class="metric">
                        <span>Recargo monetario</span>
                        <strong style="color:var(--success);">Q 0.00</strong>
                    </div>

                    <div class="metric">
                        <span>Porcentaje de mora</span>
                        <strong style="color:var(--success);">0%</strong>
                    </div>

                    <div class="metric">
                        <span>Consecuencia</span>
                        <strong>Bloqueo de nuevo crédito</strong>
                    </div>

                    <div class="metric">
                        <span>Duplicación de cargos</span>
                        <strong style="color:var(--success);">No aplica</strong>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-body" style="display:grid; gap:18px;">
                <div style="border:1px solid #bbf7d0; background:#f0fdf4; border-radius:18px; padding:16px; color:#14532d;">
                    <strong>Regla oficial:</strong>
                    si un cliente se atrasa, no se le cobra ningún extra por mora. El sistema lo marca como bloqueado para nuevos créditos.
                </div>

                <div style="border:1px solid #fed7aa; background:#fff7ed; border-radius:18px; padding:16px; color:#7c2d12;">
                    <strong>Control operativo:</strong>
                    el pago de cuotas atrasadas mantiene el monto original de la cuota. La marca de atraso queda como historial de riesgo crediticio.
                </div>

                <form method="POST" action="{{ route('settings.credit-late-fees.update') }}" style="display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap;">
                    @csrf
                    @method('PUT')

                    <a class="btn" style="background:#eef2f7;" href="{{ route('settings.index', ['group' => 'credits']) }}">Ver parámetros</a>

                    @can('settings.update')
                        <button class="btn btn-primary" type="submit">Confirmar política sin recargo</button>
                    @else
                        <span class="muted">No tienes permiso para modificar configuración.</span>
                    @endcan
                </form>
            </div>
        </section>
    </main>
</div>
@endsection
