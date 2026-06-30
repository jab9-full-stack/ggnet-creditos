<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use Database\Seeders\CreditLateFeeSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreditLateFeeSettingManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permissionNames = ['settings.view', 'settings.update']): User
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $agency = Agency::query()->create([
            'code' => 'CENTRAL',
            'name' => 'Agencia Central',
            'is_active' => true,
        ]);

        $permissions = collect($permissionNames)->map(fn (string $permission) => Permission::query()->firstOrCreate([
            'name' => $permission,
            'guard_name' => 'web',
        ]));

        $role = Role::query()->firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($permissions);

        $user = User::query()->create([
            'agency_id' => $agency->id,
            'name' => 'Admin Politica Atraso',
            'email' => 'admin-politica-atraso@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
        ]);

        $user->assignRole($role);

        return $user;
    }

    public function test_admin_can_view_no_fee_delinquency_policy_screen(): void
    {
        $admin = $this->admin();
        $this->seed(CreditLateFeeSettingSeeder::class);

        $this->actingAs($admin)
            ->get('/settings/credit-late-fees')
            ->assertStatus(200)
            ->assertSee('Política de atraso')
            ->assertSee('La mora no cobra recargos')
            ->assertSee('Bloqueo de nuevo crédito');
    }

    public function test_admin_can_confirm_no_fee_policy(): void
    {
        $admin = $this->admin();
        $this->seed(CreditLateFeeSettingSeeder::class);

        $this->actingAs($admin)
            ->put('/settings/credit-late-fees')
            ->assertRedirect('/settings/credit-late-fees');

        $this->assertDatabaseHas('settings', [
            'key' => 'credit_late_fee_enabled',
            'type' => 'boolean',
        ]);
    }

    public function test_guest_cannot_access_late_fee_policy(): void
    {
        $this->get('/settings/credit-late-fees')
            ->assertRedirect('/login');

        $this->put('/settings/credit-late-fees')
            ->assertRedirect('/login');
    }
}
