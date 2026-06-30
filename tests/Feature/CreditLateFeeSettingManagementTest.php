<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use App\Services\CreditLateFeeService;
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
            'name' => 'Admin Config Mora',
            'email' => 'admin-config-mora@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
        ]);

        $user->assignRole($role);

        return $user;
    }

    public function test_admin_can_view_late_fee_configuration_screen(): void
    {
        $admin = $this->admin();
        $this->seed(CreditLateFeeSettingSeeder::class);

        $this->actingAs($admin)
            ->get('/settings/credit-late-fees')
            ->assertStatus(200)
            ->assertSee('Configuración de mora')
            ->assertSee('Activar mora')
            ->assertSee('Monto fijo por cuota vencida');
    }

    public function test_admin_can_update_fixed_late_fee_configuration(): void
    {
        $admin = $this->admin();
        $this->seed(CreditLateFeeSettingSeeder::class);

        $this->actingAs($admin)
            ->put('/settings/credit-late-fees', [
                'enabled' => '1',
                'type' => CreditLateFeeService::TYPE_FIXED,
                'fixed_amount' => '25.50',
                'percentage' => '0.0000',
                'grace_days' => '2',
            ])
            ->assertRedirect('/settings/credit-late-fees');

        $configuration = app(CreditLateFeeService::class)->configuration();

        $this->assertTrue($configuration['enabled']);
        $this->assertSame(CreditLateFeeService::TYPE_FIXED, $configuration['type']);
        $this->assertSame(25.50, $configuration['fixed_amount']);
        $this->assertSame(0.0, $configuration['percentage']);
        $this->assertSame(2, $configuration['grace_days']);
    }

    public function test_admin_can_update_percentage_late_fee_configuration(): void
    {
        $admin = $this->admin();
        $this->seed(CreditLateFeeSettingSeeder::class);

        $this->actingAs($admin)
            ->put('/settings/credit-late-fees', [
                'enabled' => '1',
                'type' => CreditLateFeeService::TYPE_PERCENTAGE,
                'fixed_amount' => '0.00',
                'percentage' => '5.2500',
                'grace_days' => '1',
            ])
            ->assertRedirect('/settings/credit-late-fees');

        $configuration = app(CreditLateFeeService::class)->configuration();

        $this->assertTrue($configuration['enabled']);
        $this->assertSame(CreditLateFeeService::TYPE_PERCENTAGE, $configuration['type']);
        $this->assertSame(5.25, $configuration['percentage']);
        $this->assertSame(1, $configuration['grace_days']);
    }

    public function test_enabled_fixed_late_fee_requires_positive_amount(): void
    {
        $admin = $this->admin();
        $this->seed(CreditLateFeeSettingSeeder::class);

        $this->actingAs($admin)
            ->from('/settings/credit-late-fees')
            ->put('/settings/credit-late-fees', [
                'enabled' => '1',
                'type' => CreditLateFeeService::TYPE_FIXED,
                'fixed_amount' => '0.00',
                'percentage' => '0.0000',
                'grace_days' => '0',
            ])
            ->assertRedirect('/settings/credit-late-fees')
            ->assertSessionHasErrors('fixed_amount');
    }

    public function test_enabled_percentage_late_fee_requires_positive_percentage(): void
    {
        $admin = $this->admin();
        $this->seed(CreditLateFeeSettingSeeder::class);

        $this->actingAs($admin)
            ->from('/settings/credit-late-fees')
            ->put('/settings/credit-late-fees', [
                'enabled' => '1',
                'type' => CreditLateFeeService::TYPE_PERCENTAGE,
                'fixed_amount' => '0.00',
                'percentage' => '0.0000',
                'grace_days' => '0',
            ])
            ->assertRedirect('/settings/credit-late-fees')
            ->assertSessionHasErrors('percentage');
    }

    public function test_guest_cannot_access_late_fee_configuration(): void
    {
        $this->get('/settings/credit-late-fees')
            ->assertRedirect('/login');

        $this->put('/settings/credit-late-fees')
            ->assertRedirect('/login');
    }
}
