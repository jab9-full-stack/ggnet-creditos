<section class="panel">
    <div class="panel-body">
        @if (isset($errors) && $errors->any())
            <div class="inline-error-source" data-toast-type="error" data-toast-title="Revisa la información" data-toast-message="{{ $errors->first() }}"></div>
        @endif

        <form method="POST" action="{{ $action }}" id="agency-form">
            @csrf
            @if ($method !== 'POST')
                @method($method)
            @endif

            <div class="form-grid-uniform">
                <label class="form-group">
                    <span class="label">Código <span style="color:var(--danger);">*</span></span>
                    <input
                        class="input"
                        id="agency-code"
                        value="{{ old('code', $agency->code) }}"
                        placeholder="Se genera automáticamente"
                        readonly
                        style="background:#f3f4f6; cursor:not-allowed;"
                    >
                    <small class="muted">Se genera automáticamente desde el nombre.</small>
                </label>

                <label class="form-group span-2">
                    <span class="label">Nombre de agencia <span style="color:var(--danger);">*</span></span>
                    <input
                        class="input"
                        id="agency-name"
                        name="name"
                        value="{{ old('name', $agency->name) }}"
                        required
                    >
                </label>

                <label class="form-group">
                    <span class="label">Razón social <span style="color:var(--danger);">*</span></span>
                    <input
                        class="input"
                        name="legal_name"
                        value="{{ old('legal_name', $agency->legal_name) }}"
                        required
                    >
                </label>

                <label class="form-group">
                    <span class="label">NIT <span style="color:var(--danger);">*</span></span>
                    <input
                        class="input only-numbers"
                        name="tax_id"
                        value="{{ old('tax_id', $agency->tax_id) }}"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        required
                    >
                </label>

                <label class="form-group">
                    <span class="label">Teléfono <span style="color:var(--danger);">*</span></span>
                    <input
                        class="input only-numbers"
                        name="phone"
                        value="{{ old('phone', $agency->phone) }}"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        required
                    >
                </label>

                <label class="form-group">
                    <span class="label">Correo</span>
                    <input class="input" type="email" name="email" value="{{ old('email', $agency->email) }}">
                </label>

                <label class="form-group">
                    <span class="label">País <span style="color:var(--danger);">*</span></span>
                    <input class="input" name="country" value="{{ old('country', $agency->country ?? 'Guatemala') }}" required>
                </label>

                <label class="form-group span-3">
                    <span class="label">Dirección <span style="color:var(--danger);">*</span></span>
                    <input
                        class="input"
                        name="address_line"
                        value="{{ old('address_line', $agency->address_line) }}"
                        required
                    >
                </label>

                <label class="form-group">
                    <span class="label">Ciudad / Municipio</span>
                    <input class="input" name="city" value="{{ old('city', $agency->city) }}">
                </label>

                <label class="form-group">
                    <span class="label">Departamento</span>
                    <input class="input" name="department" value="{{ old('department', $agency->department) }}">
                </label>

                <label class="form-group" style="display:flex; align-items:center; gap:10px; min-height:46px;">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $agency->is_active ?? true))>
                    <span class="label">Agencia activa</span>
                </label>
            </div>

            <p class="muted" style="margin-top:18px; font-size:13px;">
                Los campos marcados con <span style="color:var(--danger); font-weight:800;">*</span> son obligatorios.
            </p>

            <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
                <a class="btn" style="background:#eef2f7;" href="{{ route('agencies.index') }}">Cancelar</a>
                <button class="btn btn-primary" type="submit">{{ $buttonText }}</button>
            </div>
        </form>
    </div>
</section>
