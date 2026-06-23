<section class="panel">
    <div class="panel-body">
        @if (isset($errors) && $errors->any())
            <div class="inline-error-source" data-toast-type="error" data-toast-title="Revisa la información" data-toast-message="{{ $errors->first() }}"></div>
        @endif

        <form method="POST" action="{{ $action }}" enctype="multipart/form-data">
            @csrf
            @if ($method !== 'POST')
                @method($method)
            @endif

            <div class="form-grid-uniform">
                <label class="form-group">
                    <span class="label">Tipo <span style="color:var(--danger);">*</span></span>
                    <select class="input" name="type" required>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected(old('type', $document->type ?? 'dpi_front') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="form-group span-2">
                    <span class="label">Título <span style="color:var(--danger);">*</span></span>
                    <input class="input" name="title" value="{{ old('title', $document->title) }}" required>
                </label>

                <label class="form-group">
                    <span class="label">Estado <span style="color:var(--danger);">*</span></span>
                    <select class="input" name="status" required>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $document->status ?? 'pending') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                @if ($requiresFile)
                    <label class="form-group span-3">
                        <span class="label">Archivo <span style="color:var(--danger);">*</span></span>
                        <input class="input" type="file" name="file" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf" required>
                        <small class="muted">Permitidos: JPG, JPEG, PNG, WEBP y PDF. Máximo 10 MB.</small>
                    </label>
                @else
                    <div class="form-group span-3">
                        <span class="label">Archivo actual</span>
                        <div class="input" style="background:#f3f4f6;">
                            {{ $document->original_name }} · {{ $document->readableSize() }}
                        </div>
                        <small class="muted">Para conservar trazabilidad, el archivo no se reemplaza desde esta pantalla.</small>
                    </div>
                @endif

                <label class="form-group span-3">
                    <span class="label">Observaciones</span>
                    <textarea class="input" name="notes" rows="4">{{ old('notes', $document->notes) }}</textarea>
                </label>
            </div>

            <p class="muted" style="margin-top:18px; font-size:13px;">
                Los campos marcados con <span style="color:var(--danger); font-weight:800;">*</span> son obligatorios.
            </p>

            <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
                <a class="btn" style="background:#eef2f7;" href="{{ route('clients.documents.index', $client) }}">Cancelar</a>
                <button class="btn btn-primary" type="submit">{{ $buttonText }}</button>
            </div>
        </form>
    </div>
</section>
