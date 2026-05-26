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

            <div class="form-grid-uniform">
                <label class="form-group">
                    <span class="label">Nombre <span style="color:var(--danger);">*</span></span>
                    <input class="input" name="name" value="{{ old('name', $user->name) }}" required>
                </label>

                <label class="form-group">
                    <span class="label">Correo <span style="color:var(--danger);">*</span></span>
                    <input class="input" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                </label>

                <label class="form-group">
                    <span class="label">Agencia</span>
                    <select class="input" name="agency_id">
                        <option value="">Sin agencia</option>
                        @foreach ($agencies as $agency)
                            <option value="{{ $agency->id }}" @selected((string) old('agency_id', $user->agency_id) === (string) $agency->id)>
                                {{ $agency->code }} · {{ $agency->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="form-group">
                    <span class="label">Contraseña @if($passwordRequired)<span style="color:var(--danger);">*</span>@endif</span>
                    <input class="input" type="password" name="password" autocomplete="new-password" @required($passwordRequired)>
                    @unless($passwordRequired)
                        <small class="muted">Déjala vacía para conservar la contraseña actual.</small>
                    @endunless
                </label>

                <label class="form-group">
                    <span class="label">Confirmar contraseña @if($passwordRequired)<span style="color:var(--danger);">*</span>@endif</span>
                    <input class="input" type="password" name="password_confirmation" autocomplete="new-password" @required($passwordRequired)>
                    <small class="password-hint" data-password-match-message></small>
                </label>

                <label class="form-group">
                    <span class="label">Estado <span style="color:var(--danger);">*</span></span>
                    <select class="input" name="status" id="user-status" required>
                        <option value="active" @selected(old('status', $user->status ?? 'active') === 'active')>Activo</option>
                        <option value="inactive" @selected(old('status', $user->status) === 'inactive')>Inactivo</option>
                        <option value="blocked" @selected(old('status', $user->status) === 'blocked')>Bloqueado</option>
                    </select>
                </label>

                <label class="form-group span-3">
                    <span class="label">Motivo de bloqueo</span>
                    <input
                        class="input"
                        name="blocked_reason"
                        value="{{ old('blocked_reason', $user->blocked_reason) }}"
                        placeholder="Obligatorio solo si el estado es Bloqueado"
                    >
                </label>
            </div>

            <div style="margin-top:22px;">
                <span class="label">Roles</span>
                <div class="form-grid-uniform" style="margin-top:8px;">
                    @foreach ($roles as $role)
                        <label style="display:flex; align-items:center; gap:10px; padding:12px; border:1px solid var(--line); border-radius:14px; background:#fff;">
                            <input
                                type="checkbox"
                                name="roles[]"
                                value="{{ $role->name }}"
                                @checked(in_array($role->name, old('roles', $selectedRoles), true))
                            >
                            <span style="font-weight:800;">{{ \App\Support\RoleLabels::label($role->name) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <p class="muted" style="margin-top:18px; font-size:13px;">
                Los campos marcados con <span style="color:var(--danger); font-weight:800;">*</span> son obligatorios.
            </p>

            <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
                <a class="btn" style="background:#eef2f7;" href="{{ route('users.index') }}">Cancelar</a>
                <button class="btn btn-primary" type="submit">{{ $buttonText }}</button>
            </div>
        </form>
    </div>
</section>
