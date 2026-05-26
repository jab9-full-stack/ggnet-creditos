<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $agency = Agency::query()->updateOrCreate(
            ['code' => 'CENTRAL'],
            [
                'name' => 'Agencia Central',
                'legal_name' => 'GGNET Créditos',
                'country' => 'Guatemala',
                'is_active' => true,
                'metadata' => [
                    'seeded_by' => 'core_module',
                    'purpose' => 'Agencia principal del sistema',
                ],
            ]
        );

        $permissions = [
            'dashboard.view',

            'users.view',
            'users.create',
            'users.update',
            'users.block',
            'users.assign_roles',

            'roles.view',
            'roles.manage',

            'agencies.view',
            'agencies.create',
            'agencies.update',

            'settings.view',
            'settings.update',

            'audit_logs.view',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $roles = [
            'super-admin' => $permissions,
            'admin' => [
                'dashboard.view',
                'users.view',
                'users.create',
                'users.update',
                'users.block',
                'users.assign_roles',
                'roles.view',
                'agencies.view',
                'agencies.create',
                'agencies.update',
                'settings.view',
                'settings.update',
                'audit_logs.view',
            ],
            'manager' => [
                'dashboard.view',
                'users.view',
                'agencies.view',
                'settings.view',
                'audit_logs.view',
            ],
            'analyst' => [
                'dashboard.view',
            ],
            'cashier' => [
                'dashboard.view',
            ],
            'auditor' => [
                'dashboard.view',
                'audit_logs.view',
            ],
            'support' => [
                'dashboard.view',
                'users.view',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($rolePermissions);
        }

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@ggnet.com.gt'],
            [
                'agency_id' => $agency->id,
                'name' => 'Administrador General',
                'password' => Hash::make('Cambiar.2026!'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $admin->syncRoles(['super-admin']);

        $settings = [
            [
                'group' => 'app',
                'key' => 'app.display_name',
                'value' => 'BIENESTAR GGNET Créditos',
                'type' => 'string',
                'description' => 'Nombre visible del sistema.',
                'is_public' => true,
                'is_locked' => true,
            ],
            [
                'group' => 'app',
                'key' => 'app.country',
                'value' => 'Guatemala',
                'type' => 'string',
                'description' => 'País operativo base.',
                'is_public' => false,
                'is_locked' => true,
            ],
            [
                'group' => 'security',
                'key' => 'security.session_timeout_minutes',
                'value' => 120,
                'type' => 'integer',
                'description' => 'Tiempo sugerido de expiración de sesión administrativa.',
                'is_public' => false,
                'is_locked' => false,
            ],
            [
                'group' => 'audit',
                'key' => 'audit.enabled',
                'value' => true,
                'type' => 'boolean',
                'description' => 'Activa el registro de auditoría del sistema.',
                'is_public' => false,
                'is_locked' => true,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::query()->updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
