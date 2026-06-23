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
                    <span class="label">Tipo <span style="color:var(--danger);">*</span></span>
                    <select class="input" name="type" required>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected(old('type', $reference->type ?? 'personal') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="form-group span-2">
                    <span class="label">Nombre completo <span style="color:var(--danger);">*</span></span>
                    <input class="input" name="full_name" value="{{ old('full_name', $reference->full_name) }}" required>
                </label>

                <label class="form-group">
                    <span class="label">Relación / Parentesco</span>
                    <input class="input" name="relationship" value="{{ old('relationship', $reference->relationship) }}" placeholder="Ej. Madre, vecino, jefe, compañero">
                </label>

                <label class="form-group">
                    <span class="label">Teléfono principal <span style="color:var(--danger);">*</span></span>
                    <input class="input only-numbers" name="phone" value="{{ old('phone', $reference->phone) }}" inputmode="numeric" pattern="[0-9]*" required>
                </label>

                <label class="form-group">
                    <span class="label">Teléfono secundario</span>
                    <input class="input only-numbers" name="secondary_phone" value="{{ old('secondary_phone', $reference->secondary_phone) }}" inputmode="numeric" pattern="[0-9]*">
                </label>

                <label class="form-group span-2">
                    <span class="label">Dirección</span>
                    <input class="input" name="address_line" value="{{ old('address_line', $reference->address_line) }}">
                </label>

                <label class="form-group">
                    <span class="label">Lugar de trabajo</span>
                    <input class="input" name="workplace" value="{{ old('workplace', $reference->workplace) }}">
                </label>

                <label class="form-group" style="display:flex; align-items:center; gap:10px; min-height:46px;">
                    <input type="checkbox" name="is_primary" value="1" @checked(old('is_primary', $reference->is_primary ?? false))>
                    <span class="label">Referencia principal</span>
                </label>

                <label class="form-group span-3">
                    <span class="label">Observaciones</span>
                    <textarea class="input" name="notes" rows="4">{{ old('notes', $reference->notes) }}</textarea>
                </label>
            </div>

            <p class="muted" style="margin-top:18px; font-size:13px;">
                Los campos marcados con <span style="color:var(--danger); font-weight:800;">*</span> son obligatorios.
            </p>

            <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
                <a class="btn" style="background:#eef2f7;" href="{{ route('clients.references.index', $client) }}">Cancelar</a>
                <button class="btn btn-primary" type="submit">{{ $buttonText }}</button>
            </div>
        </form>
    </div>
</section>
