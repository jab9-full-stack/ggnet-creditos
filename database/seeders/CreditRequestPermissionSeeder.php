<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreditRequestPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'credit_requests.view',
            'credit_requests.create',
            'credit_requests.update',
            'credit_requests.review',
            'credit_requests.approve',
            'credit_requests.reject',
            'credit_requests.delete',
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
                'credit_requests.view',
                'credit_requests.review',
                'credit_requests.approve',
                'credit_requests.reject',
            ],
            'analyst' => [
                'credit_requests.view',
                'credit_requests.create',
                'credit_requests.update',
                'credit_requests.review',
            ],
            'cashier' => [
                'credit_requests.view',
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
