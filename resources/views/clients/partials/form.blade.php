<section class="panel">
    <div class="panel-body">
        @if (isset($errors) && $errors->any())
            <div class="inline-error-source" data-toast-type="error" data-toast-title="Revisa la información" data-toast-message="{{ $errors->first() }}"></div>
        @endif

        <form method="POST" action="{{ $action }}" id="client-form">
            @csrf
            @if ($method !== 'POST')
                @method($method)
            @endif

            <h2 style="margin:0 0 6px; font-size:18px;">Identidad</h2>
            <p class="muted" style="margin:0 0 12px;">Datos principales del expediente base.</p>

            <div class="form-grid-uniform">
                <label class="form-group">
                    <span class="label">Código</span>
                    <input class="input" value="{{ old('code', $client->code) }}" placeholder="Se genera automáticamente" readonly style="background:#f3f4f6; cursor:not-allowed;">
                </label>

                <label class="form-group">
                    <span class="label">Agencia</span>
                    <select class="input" name="agency_id">
                        <option value="">Sin agencia</option>
                        @foreach ($agencies as $agency)
                            <option value="{{ $agency->id }}" @selected((string) old('agency_id', $client->agency_id) === (string) $agency->id)>
                                {{ $agency->code }} · {{ $agency->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="form-group">
                    <span class="label">Estado <span style="color:var(--danger);">*</span></span>
                    <select class="input" name="status" required>
                        <option value="active" @selected(old('status', $client->status ?? 'active') === 'active')>Activo</option>
                        <option value="inactive" @selected(old('status', $client->status) === 'inactive')>Inactivo</option>
                    </select>
                </label>

                <label class="form-group">
                    <span class="label">Primer nombre <span style="color:var(--danger);">*</span></span>
                    <input class="input" name="first_name" value="{{ old('first_name', $client->first_name) }}" required>
                </label>

                <label class="form-group">
                    <span class="label">Segundo nombre</span>
                    <input class="input" name="middle_name" value="{{ old('middle_name', $client->middle_name) }}">
                </label>

                <label class="form-group">
                    <span class="label">Primer apellido <span style="color:var(--danger);">*</span></span>
                    <input class="input" name="last_name" value="{{ old('last_name', $client->last_name) }}" required>
                </label>

                <label class="form-group">
                    <span class="label">Segundo apellido</span>
                    <input class="input" name="second_last_name" value="{{ old('second_last_name', $client->second_last_name) }}">
                </label>

                <label class="form-group">
                    <span class="label">Apellido de casada</span>
                    <input class="input" name="married_name" value="{{ old('married_name', $client->married_name) }}">
                </label>

                <label class="form-group">
                    <span class="label">DPI <span style="color:var(--danger);">*</span></span>
                    <input class="input only-numbers" name="dpi" value="{{ old('dpi', $client->dpi) }}" inputmode="numeric" pattern="[0-9]*" maxlength="20" required>
                </label>

                <label class="form-group">
                    <span class="label">NIT</span>
                    <input class="input only-numbers" name="nit" value="{{ old('nit', $client->nit) }}" inputmode="numeric" pattern="[0-9]*" maxlength="20">
                </label>

                <label class="form-group">
                    <span class="label">Fecha de nacimiento</span>
                    <input class="input" type="date" name="birth_date" value="{{ old('birth_date', $client->birth_date?->format('Y-m-d')) }}">
                </label>

                <label class="form-group">
                    <span class="label">Género</span>
                    <select class="input" name="gender">
                        <option value="">No especificado</option>
                        <option value="female" @selected(old('gender', $client->gender) === 'female')>Femenino</option>
                        <option value="male" @selected(old('gender', $client->gender) === 'male')>Masculino</option>
                        <option value="other" @selected(old('gender', $client->gender) === 'other')>Otro</option>
                    </select>
                </label>
            </div>

            <h2 style="margin:28px 0 6px; font-size:18px;">Contacto y ubicación</h2>
            <p class="muted" style="margin:0 0 12px;">Datos mínimos para localizar al cliente.</p>

            <div class="form-grid-uniform">
                <label class="form-group">
                    <span class="label">Teléfono principal <span style="color:var(--danger);">*</span></span>
                    <input class="input only-numbers" name="phone" value="{{ old('phone', $client->phone) }}" inputmode="numeric" pattern="[0-9]*" required>
                </label>

                <label class="form-group">
                    <span class="label">Teléfono secundario</span>
                    <input class="input only-numbers" name="secondary_phone" value="{{ old('secondary_phone', $client->secondary_phone) }}" inputmode="numeric" pattern="[0-9]*">
                </label>

                <label class="form-group">
                    <span class="label">Correo</span>
                    <input class="input" type="email" name="email" value="{{ old('email', $client->email) }}">
                </label>

                <label class="form-group span-3">
                    <span class="label">Dirección <span style="color:var(--danger);">*</span></span>
                    <input class="input" name="address_line" value="{{ old('address_line', $client->address_line) }}" required>
                </label>

                <label class="form-group">
                    <span class="label">Municipio</span>
                    <input class="input" name="city" value="{{ old('city', $client->city) }}">
                </label>

                <label class="form-group">
                    <span class="label">Departamento</span>
                    <input class="input" name="department" value="{{ old('department', $client->department) }}">
                </label>

                <label class="form-group">
                    <span class="label">País <span style="color:var(--danger);">*</span></span>
                    <input class="input" name="country" value="{{ old('country', $client->country ?? 'Guatemala') }}" required>
                </label>
            </div>

            <h2 style="margin:28px 0 6px; font-size:18px;">Información adicional</h2>
            <p class="muted" style="margin:0 0 12px;">Campos descriptivos. No son solicitud de crédito todavía.</p>

            <div class="form-grid-uniform">
                <label class="form-group">
                    <span class="label">Ocupación</span>
                    <input class="input" name="occupation" value="{{ old('occupation', $client->occupation) }}">
                </label>

                <label class="form-group">
                    <span class="label">Lugar de trabajo</span>
                    <input class="input" name="workplace" value="{{ old('workplace', $client->workplace) }}">
                </label>

                <label class="form-group span-3">
                    <span class="label">Observaciones</span>
                    <textarea class="input" name="notes" rows="4">{{ old('notes', $client->notes) }}</textarea>
                </label>
            </div>

            <p class="muted" style="margin-top:18px; font-size:13px;">
                Los campos marcados con <span style="color:var(--danger); font-weight:800;">*</span> son obligatorios.
            </p>

            <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
                <a class="btn" style="background:#eef2f7;" href="{{ route('clients.index') }}">Cancelar</a>
                <button class="btn btn-primary" type="submit">{{ $buttonText }}</button>
            </div>
        </form>
    </div>
</section>
