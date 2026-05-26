@extends('layouts.app')

@section('content')
<div class="app-shell">
    @include('partials.sidebar')

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-title">Configuración</h1>
                <p class="page-subtitle">Parámetros globales del sistema. Los bloqueados se muestran solo como referencia.</p>
            </div>
        </header>

        @if (session('status'))
            <div hidden data-toast-type="success" data-toast-title="Operación completada" data-toast-message="{{ session('status') }}"></div>
        @endif

        @if (session('error'))
            <div hidden data-toast-type="error" data-toast-title="No se pudo completar" data-toast-message="{{ session('error') }}"></div>
        @endif

        @if (isset($errors) && $errors->any())
            <div hidden data-toast-type="error" data-toast-title="Revisa la información" data-toast-message="{{ $errors->first() }}"></div>
        @endif

        <section class="panel" style="margin-bottom:18px;">
            <div class="panel-body">
                <form method="GET" action="{{ route('settings.index') }}" style="display:flex; gap:12px; align-items:center;">
                    <select class="input" name="group" style="max-width:280px;">
                        <option value="">Todos los grupos</option>
                        @foreach ($groups as $item)
                            <option value="{{ $item }}" @selected($group === $item)>
                                {{ \App\Support\Settings\SettingPresenter::groupLabel($item) }}
                            </option>
                        @endforeach
                    </select>

                    <button class="btn btn-primary" type="submit">Filtrar</button>
                    <a class="btn" style="background:#eef2f7;" href="{{ route('settings.index') }}">Limpiar</a>
                </form>
            </div>
        </section>

        <section class="panel">
            <div class="panel-body">
                <div class="table-scroll">
                    <table class="table compact-table">
                        <thead>
                            <tr>
                                <th>Configuración</th>
                                <th>Grupo</th>
                                <th>Valor</th>
                                <th>Tipo</th>
                                <th>Público</th>
                                <th>Estado</th>
                                <th style="text-align:right;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($settings as $setting)
                                @php
                                    $displayValue = \App\Support\Settings\SettingPresenter::displayValue($setting);
                                @endphp

                                <tr>
                                    <form method="POST" action="{{ route('settings.update', $setting) }}">
                                        @csrf
                                        @method('PUT')

                                        <td style="min-width:300px;">
                                            <strong>{{ \App\Support\Settings\SettingPresenter::title($setting) }}</strong>
                                            <div class="muted" style="max-width:520px;">
                                                {{ \App\Support\Settings\SettingPresenter::help($setting) }}
                                            </div>
                                            <details style="margin-top:4px;">
                                                <summary class="muted" style="cursor:pointer;">Clave técnica</summary>
                                                <code>{{ $setting->key }}</code>
                                            </details>
                                        </td>

                                        <td>{{ \App\Support\Settings\SettingPresenter::groupLabel($setting->group) }}</td>

                                        <td style="min-width:230px;">
                                            <input
                                                class="input"
                                                name="value"
                                                value="{{ old('value', $displayValue) }}"
                                                @disabled($setting->is_locked)
                                            >
                                        </td>

                                        <td style="min-width:180px;">
                                            <select class="input" name="type" @disabled($setting->is_locked)>
                                                @foreach (['string', 'integer', 'boolean', 'decimal', 'json'] as $type)
                                                    <option value="{{ $type }}" @selected($setting->type === $type)>
                                                        {{ \App\Support\Settings\SettingPresenter::typeLabel($type) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>

                                        <td>
                                            <input type="checkbox" name="is_public" value="1" @checked($setting->is_public) @disabled($setting->is_locked)>
                                        </td>

                                        <td>
                                            @if ($setting->is_locked)
                                                <span style="color:var(--warning); font-weight:900;">Bloqueado</span>
                                            @else
                                                <span style="color:var(--success); font-weight:900;">Editable</span>
                                            @endif
                                        </td>

                                        <td style="text-align:right;">
                                            @if ($setting->is_locked)
                                                <span class="muted">Sin acciones</span>
                                            @else
                                                @can('settings.update')
                                                    <button class="btn btn-primary" type="submit">Guardar</button>
                                                @else
                                                    <span class="muted">Sin permisos</span>
                                                @endcan
                                            @endif
                                        </td>
                                    </form>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="muted">No hay settings registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div style="margin-top:18px;">
                    {{ $settings->links() }}
                </div>
            </div>
        </section>
    </main>
</div>
@endsection
