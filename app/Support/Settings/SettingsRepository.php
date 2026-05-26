<?php

namespace App\Support\Settings;

use App\Models\Setting;
use App\Models\User;
use App\Support\Audit\AuditLogger;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class SettingsRepository
{
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever($this->cacheKey($key), function () use ($key, $default) {
            $setting = Setting::query()->where('key', $key)->first();

            return $setting?->value ?? $default;
        });
    }

    public function public(): array
    {
        return Cache::rememberForever('settings.public', function () {
            return Setting::query()
                ->where('is_public', true)
                ->orderBy('key')
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    public function set(
        string $key,
        mixed $value,
        string $group = 'general',
        string $type = 'string',
        ?string $description = null,
        bool $isPublic = false,
        bool $isLocked = false,
        ?User $updatedBy = null,
    ): Setting {
        $existing = Setting::query()->where('key', $key)->first();

        if ($existing?->is_locked && ! $isLocked) {
            throw new InvalidArgumentException("El setting [$key] está bloqueado y no puede modificarse desde este flujo.");
        }

        $oldValues = $existing?->toArray() ?? [];

        $setting = Setting::query()->updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'value' => $value,
                'type' => $type,
                'description' => $description,
                'is_public' => $isPublic,
                'is_locked' => $isLocked,
                'updated_by' => $updatedBy?->id,
            ]
        );

        $this->forget($key);

        app(AuditLogger::class)->log(
            event: $existing ? 'setting.updated' : 'setting.created',
            module: 'settings',
            auditable: $setting,
            oldValues: $oldValues,
            newValues: $setting->fresh()->toArray(),
            user: $updatedBy,
        );

        return $setting;
    }

    public function forget(string $key): void
    {
        Cache::forget($this->cacheKey($key));
        Cache::forget('settings.public');
    }

    public function flush(): void
    {
        Cache::forget('settings.public');

        Setting::query()
            ->pluck('key')
            ->each(fn (string $key) => Cache::forget($this->cacheKey($key)));
    }

    private function cacheKey(string $key): string
    {
        return 'settings.key.'.$key;
    }
}
