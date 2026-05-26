<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_and_access_dashboard(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $agency = Agency::query()->create([
            'code' => 'CENTRAL',
            'name' => 'Agencia Central',
            'is_active' => true,
        ]);

        $permission = Permission::query()->create([
            'name' => 'dashboard.view',
            'guard_name' => 'web',
        ]);

        $role = Role::query()->create([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);

        $role->givePermissionTo($permission);

        $admin = User::query()->create([
            'agency_id' => $agency->id,
            'name' => 'Administrador General',
            'email' => 'admin@ggnet.com.gt',
            'password' => Hash::make('Cambiar.2026!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $admin->assignRole($role);

        $response = $this->post('/login', [
            'email' => 'admin@ggnet.com.gt',
            'password' => 'Cambiar.2026!',
        ]);

        $response->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($admin);

        $this->get('/dashboard')
            ->assertStatus(200)
            ->assertSee('Dashboard inicial');
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get('/dashboard')
            ->assertRedirect('/login');
    }
}
