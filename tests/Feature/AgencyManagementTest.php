<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AgencyManagementTest extends TestCase
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
            'agencies.view',
            'agencies.create',
            'agencies.update',
            'agencies.delete',
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
            'name' => 'Admin Test',
            'email' => 'admin-agencies@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
        ]);

        $user->assignRole($role);

        return $user;
    }

    public function test_admin_can_view_agencies_index(): void
    {
        $this->actingAs($this->admin())
            ->get('/agencies')
            ->assertStatus(200)
            ->assertSee('Agencias')
            ->assertSee('Agencia Central');
    }

    public function test_admin_can_create_agency(): void
    {
        $this->actingAs($this->admin())
            ->post('/agencies', [
                'name' => 'Agencia Sur',
                'legal_name' => 'Agencia Sur S.A.',
                'tax_id' => '1234567',
                'phone' => '50255550000',
                'address_line' => 'Zona 1',
                'country' => 'Guatemala',
                'is_active' => '1',
            ])
            ->assertRedirect('/agencies');

        $this->assertDatabaseHas('agencies', [
            'code' => 'AGENCIA_SUR',
            'name' => 'Agencia Sur',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'created',
            'module' => 'agencies',
            'auditable_type' => Agency::class,
        ]);
    }

    public function test_admin_can_update_agency(): void
    {
        $admin = $this->admin();

        $agency = Agency::query()->create([
            'code' => 'NORTE',
            'name' => 'Agencia Norte',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put("/agencies/{$agency->id}", [
                'name' => 'Agencia Norte Actualizada',
                'legal_name' => 'Agencia Norte S.A.',
                'tax_id' => '7654321',
                'phone' => '50244440000',
                'address_line' => 'Zona 2',
                'country' => 'Guatemala',
                'is_active' => '1',
            ])
            ->assertRedirect('/agencies');

        $this->assertDatabaseHas('agencies', [
            'id' => $agency->id,
            'name' => 'Agencia Norte Actualizada',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'updated',
            'module' => 'agencies',
            'auditable_type' => Agency::class,
            'auditable_id' => $agency->id,
        ]);
    }

    public function test_agency_requires_numeric_tax_id_and_phone(): void
    {
        $this->actingAs($this->admin())
            ->post('/agencies', [
                'name' => 'Agencia Letras',
                'legal_name' => 'Agencia Letras S.A.',
                'tax_id' => 'ABC123',
                'phone' => 'TEL555',
                'address_line' => 'Zona 3',
                'country' => 'Guatemala',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors(['tax_id', 'phone']);
    }


    public function test_admin_can_delete_agency_without_users(): void
    {
        $admin = $this->admin();

        $agency = Agency::query()->create([
            'code' => 'TEMP',
            'name' => 'Agencia Temporal',
            'legal_name' => 'Agencia Temporal S.A.',
            'tax_id' => '999999',
            'phone' => '50255551111',
            'address_line' => 'Zona 9',
            'country' => 'Guatemala',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete("/agencies/{$agency->id}")
            ->assertRedirect('/agencies');

        $this->assertSoftDeleted('agencies', [
            'id' => $agency->id,
        ]);
    }

    public function test_guest_cannot_access_agencies(): void
    {
        $this->get('/agencies')
            ->assertRedirect('/login');
    }
}
