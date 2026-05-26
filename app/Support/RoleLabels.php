<?php

namespace App\Support;

class RoleLabels
{
    public static function label(string $role): string
    {
        return match ($role) {
            'super-admin' => 'Super administrador',
            'admin' => 'Administrador',
            'manager' => 'Gerente',
            'analyst' => 'Analista',
            'cashier' => 'Cajero',
            'auditor' => 'Auditor',
            'support' => 'Soporte',
            default => str($role)->replace('-', ' ')->title()->toString(),
        };
    }
}
