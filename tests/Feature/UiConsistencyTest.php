<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UiConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect([
            'dashboard.view',
            'agencies.view',
            'users.view',
            'clients.view',
            'credit_requests.view',
            'credits.view',
            'cash.view',
            'audit_logs.view',
            'settings.view',
        ])->map(fn (string $permission) => Permission::query()->firstOrCreate([
            'name' => $permission,
            'guard_name' => 'web',
        ]));

        $role = Role::query()->firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($permissions);

        $user = User::forceCreate([
            'name' => 'Admin UI',
            'email' => 'admin-ui@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $user->assignRole($role);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    public function test_admin_pages_use_same_shell_and_main_layout(): void
    {
        $admin = $this->admin();

        $routes = [
            'dashboard',
            'agencies.index',
            'users.index',
            'clients.index',
            'credit-requests.index',
            'credits.index',
            'cash.index',
            'audit-logs.index',
            'settings.index',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($admin)->get(route($route));

            $response->assertOk();
            $response->assertSee('<div class="app-shell">', false);
            $response->assertSee('<aside class="sidebar">', false);
            $response->assertSee('<main class="main">', false);
            $response->assertDontSee('<x-app-layout>', false);
            $response->assertDontSee('main-content');
        }
    }
}
