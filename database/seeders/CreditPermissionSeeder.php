<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreditPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'credits.view',
            'credits.update',
            'credits.disburse',
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
                'credits.view',
            ],
            'analyst' => [
                'credits.view',
            ],
            'cashier' => [
                'credits.view',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissionsForRole) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->givePermissionTo($permissionsForRole);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
