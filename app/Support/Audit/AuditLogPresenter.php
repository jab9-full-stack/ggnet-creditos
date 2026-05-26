<?php

namespace App\Support\Audit;

use App\Models\AuditLog;

class AuditLogPresenter
{
    public static function eventLabel(?string $event): string
    {
        return match ($event) {
            'login' => 'Inicio de sesión',
            'logout' => 'Cierre de sesión',
            'created' => 'Creación',
            'updated' => 'Actualización',
            'deleted' => 'Eliminación',
            'setting.created' => 'Creación de configuración',
            'setting.updated' => 'Actualización de configuración',
            default => ucfirst((string) $event),
        };
    }

    public static function moduleLabel(?string $module): string
    {
        return match ($module) {
            'auth' => 'Acceso',
            'users' => 'Usuarios',
            'agencies' => 'Agencias',
            'settings' => 'Configuración',
            'testing' => 'Pruebas',
            default => $module ? ucfirst($module) : 'Sistema',
        };
    }

    public static function entityLabel(AuditLog $log): string
    {
        $type = class_basename((string) $log->auditable_type);

        $label = match ($type) {
            'User' => 'Usuario',
            'Agency' => 'Agencia',
            'Setting' => 'Configuración',
            default => $type ?: 'Sistema',
        };

        return $log->auditable_id ? "{$label} #{$log->auditable_id}" : $label;
    }

    public static function summary(AuditLog $log): string
    {
        $event = self::eventLabel($log->event);
        $module = self::moduleLabel($log->module);
        $entity = self::entityLabel($log);

        return "{$event} en {$module} · {$entity}";
    }

    public static function readableChanges(AuditLog $log): array
    {
        $old = is_array($log->old_values) ? $log->old_values : [];
        $new = is_array($log->new_values) ? $log->new_values : [];

        $keys = collect(array_keys($old))
            ->merge(array_keys($new))
            ->unique()
            ->reject(fn (string $key) => in_array($key, [
                'password',
                'remember_token',
                'created_at',
                'updated_at',
                'deleted_at',
                'email_verified_at',
            ], true))
            ->values();

        return $keys->map(function (string $key) use ($old, $new) {
            return [
                'field' => self::fieldLabel($key),
                'old' => self::formatValue($old[$key] ?? null),
                'new' => self::formatValue($new[$key] ?? null),
            ];
        })->toArray();
    }

    public static function readableContext(AuditLog $log): array
    {
        $context = is_array($log->context) ? $log->context : [];

        return collect($context)
            ->map(fn ($value, string $key) => [
                'field' => self::fieldLabel($key),
                'value' => self::formatValue($value),
            ])
            ->values()
            ->toArray();
    }

    public static function technicalPayload(AuditLog $log): string
    {
        return json_encode([
            'old_values' => $log->old_values,
            'new_values' => $log->new_values,
            'context' => $log->context,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    private static function fieldLabel(string $field): string
    {
        return match ($field) {
            'id' => 'ID',
            'agency_id' => 'Agencia',
            'name' => 'Nombre',
            'email' => 'Correo',
            'status' => 'Estado',
            'last_login_at' => 'Último acceso',
            'last_login_ip' => 'IP último acceso',
            'blocked_at' => 'Fecha de bloqueo',
            'blocked_by' => 'Bloqueado por',
            'blocked_reason' => 'Motivo de bloqueo',
            'roles' => 'Roles',
            'action' => 'Acción',
            'code' => 'Código',
            'legal_name' => 'Razón social',
            'tax_id' => 'NIT',
            'phone' => 'Teléfono',
            'address_line' => 'Dirección',
            'city' => 'Ciudad',
            'department' => 'Departamento',
            'country' => 'País',
            'is_active' => 'Activo',
            'group' => 'Grupo',
            'key' => 'Clave',
            'value' => 'Valor',
            'type' => 'Tipo',
            'description' => 'Descripción',
            'is_public' => 'Público',
            'is_locked' => 'Bloqueado',
            default => str($field)->replace('_', ' ')->title()->toString(),
        };
    }

    private static function formatValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }

        if (is_array($value)) {
            return collect($value)
                ->map(fn ($item) => is_scalar($item) ? (string) $item : json_encode($item, JSON_UNESCAPED_UNICODE))
                ->implode(', ');
        }

        return (string) $value;
    }
}
