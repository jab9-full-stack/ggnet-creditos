<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\Settings\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('settings.view'), 403);

        $group = trim((string) $request->query('group', ''));

        $settings = Setting::query()
            ->when($group !== '', fn ($query) => $query->where('group', $group))
            ->orderBy('group')
            ->orderBy('key')
            ->paginate(20)
            ->withQueryString();

        return view('settings.index', [
            'settings' => $settings,
            'group' => $group,
            'groups' => Setting::query()
                ->distinct()
                ->orderBy('group')
                ->pluck('group'),
        ]);
    }

    public function update(Request $request, Setting $setting, SettingsRepository $settingsRepository): RedirectResponse
    {
        abort_unless($request->user()?->can('settings.update'), 403);

        if ($setting->is_locked) {
            return redirect()
                ->route('settings.index')
                ->with('error', 'Este setting está bloqueado y no puede modificarse desde la interfaz.');
        }

        $data = $request->validate([
            'value' => ['nullable', 'string', 'max:500'],
            'type' => ['required', Rule::in(['string', 'integer', 'boolean', 'decimal', 'json'])],
            'description' => ['nullable', 'string', 'max:255'],
            'is_public' => ['nullable', 'boolean'],
        ], [
            'type.required' => 'El tipo es obligatorio.',
            'type.in' => 'El tipo seleccionado no es válido.',
            'value.max' => 'El valor no puede superar 500 caracteres.',
        ]);

        $value = $this->normalizeValue($data['value'] ?? null, $data['type']);

        $settingsRepository->set(
            key: $setting->key,
            value: $value,
            group: $setting->group,
            type: $data['type'],
            description: $data['description'] ?? $setting->description,
            isPublic: $request->boolean('is_public'),
            isLocked: false,
            updatedBy: $request->user(),
        );

        return redirect()
            ->route('settings.index', ['group' => $setting->group])
            ->with('status', 'Setting actualizado correctamente.');
    }

    private function normalizeValue(?string $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'decimal' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => $this->decodeJson($value),
            default => $value,
        };
    }

    private function decodeJson(?string $value): mixed
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            abort(back()->withErrors(['value' => 'El valor JSON no es válido.'])->getStatusCode());
        }

        return $decoded;
    }
}
