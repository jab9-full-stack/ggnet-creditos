<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClientManagementTest extends TestCase
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
            'clients.view',
            'clients.create',
            'clients.update',
            'clients.delete',
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
            'name' => 'Admin Clientes',
            'email' => 'admin-clients@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
        ]);

        $user->assignRole($role);

        return $user;
    }

    public function test_admin_can_view_clients_index(): void
    {
        $admin = $this->admin();

        Client::query()->create([
            'agency_id' => $admin->agency_id,
            'code' => 'CLI-000001-JUANPE',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'dpi' => '1234567890101',
            'phone' => '50255550000',
            'address_line' => 'Zona 1',
            'country' => 'Guatemala',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get('/clients')
            ->assertStatus(200)
            ->assertSee('Clientes')
            ->assertSee('Juan');
    }

    public function test_admin_can_create_client(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/clients', [
                'agency_id' => $admin->agency_id,
                'first_name' => 'María',
                'middle_name' => 'Luisa',
                'last_name' => 'Gómez',
                'second_last_name' => 'López',
                'dpi' => '1234567890102',
                'nit' => '1234567',
                'birth_date' => '1990-05-10',
                'gender' => 'female',
                'phone' => '50255551111',
                'secondary_phone' => '50255552222',
                'email' => 'maria@example.com',
                'address_line' => 'Zona 2',
                'city' => 'Guatemala',
                'department' => 'Guatemala',
                'country' => 'Guatemala',
                'occupation' => 'Comerciante',
                'workplace' => 'Negocio propio',
                'status' => 'active',
                'notes' => 'Cliente de prueba',
            ])
            ->assertRedirect('/clients');

        $client = Client::query()->where('dpi', '1234567890102')->firstOrFail();

        $this->assertSame('María Luisa Gómez López', $client->fullName());
        $this->assertSame($admin->id, $client->created_by);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'created',
            'module' => 'clients',
            'auditable_type' => Client::class,
            'auditable_id' => $client->id,
        ]);
    }

    public function test_admin_can_update_client(): void
    {
        $admin = $this->admin();

        $client = Client::query()->create([
            'agency_id' => $admin->agency_id,
            'code' => 'CLI-000002-PEDRO',
            'first_name' => 'Pedro',
            'last_name' => 'Ramírez',
            'dpi' => '1234567890103',
            'phone' => '50255553333',
            'address_line' => 'Zona 3',
            'country' => 'Guatemala',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->put("/clients/{$client->id}", [
                'agency_id' => $admin->agency_id,
                'first_name' => 'Pedro',
                'last_name' => 'Ramírez Actualizado',
                'dpi' => '1234567890103',
                'phone' => '50255554444',
                'address_line' => 'Zona 4',
                'country' => 'Guatemala',
                'status' => 'inactive',
            ])
            ->assertRedirect('/clients');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'last_name' => 'Ramírez Actualizado',
            'phone' => '50255554444',
            'status' => 'inactive',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'updated',
            'module' => 'clients',
            'auditable_type' => Client::class,
            'auditable_id' => $client->id,
        ]);
    }

    public function test_client_requires_numeric_dpi_nit_and_phone(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/clients', [
                'agency_id' => $admin->agency_id,
                'first_name' => 'Cliente',
                'last_name' => 'Inválido',
                'dpi' => 'ABC123',
                'nit' => 'NIT123',
                'phone' => 'TEL555',
                'address_line' => 'Zona 5',
                'country' => 'Guatemala',
                'status' => 'active',
            ])
            ->assertSessionHasErrors(['dpi', 'nit', 'phone']);
    }

    public function test_admin_can_delete_client(): void
    {
        $admin = $this->admin();

        $client = Client::query()->create([
            'agency_id' => $admin->agency_id,
            'code' => 'CLI-000003-DELETE',
            'first_name' => 'Cliente',
            'last_name' => 'Temporal',
            'dpi' => '1234567890104',
            'phone' => '50255556666',
            'address_line' => 'Zona 6',
            'country' => 'Guatemala',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->delete("/clients/{$client->id}")
            ->assertRedirect('/clients');

        $this->assertSoftDeleted('clients', [
            'id' => $client->id,
        ]);
    }

    public function test_guest_cannot_access_clients(): void
    {
        $this->get('/clients')
            ->assertRedirect('/login');
    }
}
