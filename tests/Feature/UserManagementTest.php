<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $agency = Agency::query()->create([
            'code' => 'CENTRAL',
            'name' => 'Agencia Central',
            'is_active' => true,
        ]);

        $permissions = collect([
            'dashboard.view',
            'users.view',
            'users.create',
            'users.update',
            'users.block',
            'users.assign_roles',
            'users.delete',
        ])->map(fn (string $permission) => Permission::query()->create([
            'name' => $permission,
            'guard_name' => 'web',
        ]));

        $role = Role::query()->create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($permissions);

        Role::query()->create([
            'name' => 'cashier',
            'guard_name' => 'web',
        ]);

        $user = User::query()->create([
            'agency_id' => $agency->id,
            'name' => 'Admin Test',
            'email' => 'admin-users@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
        ]);

        $user->assignRole($role);

        return $user;
    }

    public function test_admin_can_view_users_index(): void
    {
        $this->actingAs($this->admin())
            ->get('/users')
            ->assertStatus(200)
            ->assertSee('Usuarios')
            ->assertSee('Admin Test');
    }

    public function test_admin_can_create_user_with_role(): void
    {
        $admin = $this->admin();
        $agency = $admin->agency;

        $this->actingAs($admin)
            ->post('/users', [
                'agency_id' => $agency->id,
                'name' => 'Cajero Central',
                'email' => 'cajero@example.com',
                'password' => 'Password.2026',
                'password_confirmation' => 'Password.2026',
                'status' => 'active',
                'roles' => ['cashier'],
            ])
            ->assertRedirect('/users');

        $created = User::query()->where('email', 'cajero@example.com')->firstOrFail();

        $this->assertSame('Cajero Central', $created->name);
        $this->assertTrue($created->hasRole('cashier'));

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'created',
            'module' => 'users',
            'auditable_type' => User::class,
            'auditable_id' => $created->id,
        ]);
    }

    public function test_admin_can_update_and_block_user(): void
    {
        $admin = $this->admin();

        $target = User::query()->create([
            'agency_id' => $admin->agency_id,
            'name' => 'Usuario Operativo',
            'email' => 'operativo@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->put("/users/{$target->id}", [
                'agency_id' => $admin->agency_id,
                'name' => 'Usuario Bloqueado',
                'email' => 'operativo@example.com',
                'status' => 'blocked',
                'blocked_reason' => 'Prueba de bloqueo',
                'roles' => ['cashier'],
            ])
            ->assertRedirect('/users');

        $target->refresh();

        $this->assertSame('blocked', $target->status);
        $this->assertNotNull($target->blocked_at);
        $this->assertSame('Prueba de bloqueo', $target->blocked_reason);
        $this->assertTrue($target->hasRole('cashier'));

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'updated',
            'module' => 'users',
            'auditable_type' => User::class,
            'auditable_id' => $target->id,
        ]);
    }

    public function test_admin_cannot_deactivate_himself(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put("/users/{$admin->id}", [
                'agency_id' => $admin->agency_id,
                'name' => $admin->name,
                'email' => $admin->email,
                'status' => 'inactive',
                'roles' => ['admin'],
            ])
            ->assertSessionHasErrors(['status']);
    }


    public function test_admin_can_delete_user(): void
    {
        $admin = $this->admin();

        $target = User::query()->create([
            'agency_id' => $admin->agency_id,
            'name' => 'Usuario Eliminable',
            'email' => 'eliminable@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->delete("/users/{$target->id}")
            ->assertRedirect('/users');

        $this->assertSoftDeleted('users', [
            'id' => $target->id,
        ]);
    }

    public function test_guest_cannot_access_users(): void
    {
        $this->get('/users')
            ->assertRedirect('/login');
    }
}
