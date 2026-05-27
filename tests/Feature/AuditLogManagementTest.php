<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditLogManagementTest extends TestCase
{
    use RefreshDatabase;

    private function auditor(): User
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $agency = Agency::query()->create([
            'code' => 'CENTRAL',
            'name' => 'Agencia Central',
            'is_active' => true,
        ]);

        $permission = Permission::query()->create([
            'name' => 'audit_logs.view',
            'guard_name' => 'web',
        ]);

        $role = Role::query()->create([
            'name' => 'auditor',
            'guard_name' => 'web',
        ]);

        $role->givePermissionTo($permission);

        $user = User::query()->create([
            'agency_id' => $agency->id,
            'name' => 'Auditor Test',
            'email' => 'auditor-test@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
        ]);

        $user->assignRole($role);

        return $user;
    }

    public function test_auditor_can_view_audit_logs(): void
    {
        $user = $this->auditor();

        AuditLog::query()->create([
            'user_id' => $user->id,
            'event' => 'login',
            'module' => 'auth',
            'context' => ['source' => 'test'],
        ]);

        $this->actingAs($user)
            ->get('/audit-logs')
            ->assertStatus(200)
            ->assertSee('Bitácora de seguridad')
            ->assertSee('Inicio de sesión')
            ->assertSee('Acceso');
    }

    public function test_guest_cannot_access_audit_logs(): void
    {
        $this->get('/audit-logs')
            ->assertRedirect('/login');
    }
}
