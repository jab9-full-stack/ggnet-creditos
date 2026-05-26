<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingsManagementTest extends TestCase
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
            'settings.view',
            'settings.update',
        ])->map(fn (string $permission) => Permission::query()->create([
            'name' => $permission,
            'guard_name' => 'web',
        ]));

        $role = Role::query()->create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($permissions);

        $user = User::query()->create([
            'agency_id' => $agency->id,
            'name' => 'Settings Admin',
            'email' => 'settings-admin@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
        ]);

        $user->assignRole($role);

        return $user;
    }

    public function test_admin_can_view_settings(): void
    {
        $admin = $this->admin();

        Setting::query()->create([
            'group' => 'security',
            'key' => 'security.example',
            'value' => 'activo',
            'type' => 'string',
            'description' => 'Ejemplo',
            'is_public' => false,
            'is_locked' => false,
        ]);

        $this->actingAs($admin)
            ->get('/settings')
            ->assertStatus(200)
            ->assertSee('Configuración')
            ->assertSee('security.example');
    }

    public function test_admin_can_update_unlocked_setting(): void
    {
        $admin = $this->admin();

        $setting = Setting::query()->create([
            'group' => 'security',
            'key' => 'security.timeout',
            'value' => '120',
            'type' => 'integer',
            'description' => 'Tiempo',
            'is_public' => false,
            'is_locked' => false,
        ]);

        $this->actingAs($admin)
            ->put("/settings/{$setting->id}", [
                'value' => '180',
                'type' => 'integer',
                'description' => 'Tiempo actualizado',
                'is_public' => '1',
            ])
            ->assertRedirect('/settings?group=security');

        $this->assertDatabaseHas('settings', [
            'id' => $setting->id,
            'type' => 'integer',
            'is_public' => true,
        ]);

        $this->assertSame(180, Setting::query()->find($setting->id)->value);
    }

    public function test_locked_setting_cannot_be_updated_from_ui(): void
    {
        $admin = $this->admin();

        $setting = Setting::query()->create([
            'group' => 'app',
            'key' => 'app.locked',
            'value' => 'no tocar',
            'type' => 'string',
            'description' => 'Bloqueado',
            'is_public' => false,
            'is_locked' => true,
        ]);

        $this->actingAs($admin)
            ->put("/settings/{$setting->id}", [
                'value' => 'cambio',
                'type' => 'string',
                'description' => 'Bloqueado',
            ])
            ->assertRedirect('/settings');

        $this->assertSame('no tocar', Setting::query()->find($setting->id)->value);
    }

    public function test_guest_cannot_access_settings(): void
    {
        $this->get('/settings')
            ->assertRedirect('/login');
    }
}
