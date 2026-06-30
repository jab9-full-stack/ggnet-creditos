<?php

namespace App\Support\Settings;

use App\Models\Setting;

class SettingPresenter
{
    public static function title(Setting $setting): string
    {
        return match ($setting->key) {
            'credit_late_fee_enabled' => 'Política de mora sin recargo',
            'credit_late_fee_type' => 'Tipo de mora',
            'credit_late_fee_fixed_amount' => 'Monto fijo de mora (siempre Q0.00)',
            'credit_late_fee_percentage' => 'Porcentaje de mora (siempre 0%)',
            'credit_late_fee_grace_days' => 'Días de gracia',
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
            'credit_late_fee_enabled' => 'La política vigente no cobra recargos; el atraso bloquea nuevos créditos.',
            'credit_late_fee_type' => 'Monto fijo o porcentaje sobre capital más interés de la cuota.',
            'credit_late_fee_fixed_amount' => 'Debe permanecer en Q0.00. La mora no genera cobro adicional.',
            'credit_late_fee_percentage' => 'Debe permanecer en 0%. La mora no genera cobro adicional.',
            'credit_late_fee_grace_days' => 'Cantidad de días de gracia antes de aplicar mora.',
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
            'credits' => 'Créditos y cartera',
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
