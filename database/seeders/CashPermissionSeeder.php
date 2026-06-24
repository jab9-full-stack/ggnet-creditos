<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CashPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'cash.view',
            'cash.open',
            'cash.close',
            'cash_movements.view',
            'cash_movements.create',
            'cash_movements.void',
            'cash.reports',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $rolePermissions = [
            'super-admin' => $permissions,
            'admin' => $permissions,
            'manager' => [
                'cash.view',
                'cash.open',
                'cash.close',
                'cash_movements.view',
                'cash_movements.create',
                'cash_movements.void',
                'cash.reports',
            ],
            'cashier' => [
                'cash.view',
                'cash.open',
                'cash.close',
                'cash_movements.view',
                'cash_movements.create',
            ],
            'analyst' => [
                'cash.view',
                'cash_movements.view',
            ],
            'auditor' => [
                'cash.view',
                'cash_movements.view',
                'cash.reports',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissionsForRole) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->first();

            if ($role) {
                $role->givePermissionTo($permissionsForRole);
            }
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
