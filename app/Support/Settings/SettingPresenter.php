<?php

namespace App\Support\Settings;

use App\Models\Setting;

class SettingPresenter
{
    public static function title(Setting $setting): string
    {
        return match ($setting->key) {
            'app.display_name' => 'Nombre visible del sistema',
            'app.country' => 'País operativo',
            'security.session_timeout_minutes' => 'Tiempo de sesión administrativa',
            'audit.enabled' => 'Auditoría del sistema',
            default => str($setting->key)->replace(['.', '_'], ' ')->title()->toString(),
        };
    }

    public static function help(Setting $setting): string
    {
        return match ($setting->key) {
            'app.display_name' => 'Nombre que aparece en pantallas, encabezados y referencias internas.',
            'app.country' => 'País base para la operación inicial del sistema.',
            'security.session_timeout_minutes' => 'Cantidad de minutos sugeridos antes de expirar una sesión administrativa.',
            'audit.enabled' => 'Controla si el sistema registra eventos sensibles de auditoría.',
            default => $setting->description ?: 'Parámetro global del sistema.',
        };
    }

    public static function groupLabel(string $group): string
    {
        return match ($group) {
            'app' => 'Aplicación',
            'security' => 'Seguridad',
            'audit' => 'Auditoría',
            default => ucfirst($group),
        };
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'string' => 'Texto',
            'integer' => 'Número entero',
            'boolean' => 'Sí / No',
            'decimal' => 'Decimal',
            'json' => 'JSON técnico',
            default => ucfirst($type),
        };
    }

    public static function displayValue(Setting $setting): string
    {
        if (is_array($setting->value)) {
            return json_encode($setting->value, JSON_UNESCAPED_UNICODE) ?: '';
        }

        if (is_bool($setting->value)) {
            return $setting->value ? 'true' : 'false';
        }

        return (string) $setting->value;
    }
}
