<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CashUiSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect([
            'cash.view',
            'cash.open',
            'cash.close',
            'cash_movements.create',
            'cash_movements.void',
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
            'name' => 'Admin Caja UI',
            'email' => 'admin-caja-ui@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $user->assignRole($role);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    public function test_cash_index_uses_real_app_layout_wrapper(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('cash.index'));

        $response->assertOk();
        $response->assertSee('<div class="app-shell">', false);
        $response->assertSee('<aside class="sidebar">', false);
        $response->assertSee('<main class="main">', false);
        $response->assertSee('Abrir caja');
        $response->assertSee('Sin caja abierta');
        $response->assertDontSee('main-content');
        $response->assertDontSee('<x-app-layout>', false);
    }
}
