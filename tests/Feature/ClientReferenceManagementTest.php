<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Client;
use App\Models\ClientReference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClientReferenceManagementTest extends TestCase
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
            'client_references.view',
            'client_references.create',
            'client_references.update',
            'client_references.delete',
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
            'name' => 'Admin Referencias',
            'email' => 'admin-references@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
        ]);

        $user->assignRole($role);

        return $user;
    }

    private function client(User $admin): Client
    {
        return Client::query()->create([
            'agency_id' => $admin->agency_id,
            'code' => 'CLI-000010-TEST',
            'first_name' => 'Cliente',
            'last_name' => 'Prueba',
            'dpi' => '1234567890110',
            'phone' => '50255550010',
            'address_line' => 'Zona 10',
            'country' => 'Guatemala',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_client_references(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);

        ClientReference::query()->create([
            'client_id' => $client->id,
            'type' => 'family',
            'full_name' => 'Referencia Familiar',
            'relationship' => 'Madre',
            'phone' => '50255551111',
            'is_primary' => true,
        ]);

        $this->actingAs($admin)
            ->get("/clients/{$client->id}/references")
            ->assertStatus(200)
            ->assertSee('Referencias')
            ->assertSee('Referencia Familiar');
    }

    public function test_admin_can_create_reference(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);

        $this->actingAs($admin)
            ->post("/clients/{$client->id}/references", [
                'type' => 'family',
                'full_name' => 'Ana López',
                'relationship' => 'Madre',
                'phone' => '50255551111',
                'secondary_phone' => '50255552222',
                'address_line' => 'Zona 1',
                'workplace' => 'Casa',
                'notes' => 'Referencia confiable',
                'is_primary' => '1',
            ])
            ->assertRedirect("/clients/{$client->id}/references");

        $reference = ClientReference::query()->where('full_name', 'Ana López')->firstOrFail();

        $this->assertTrue($reference->is_primary);
        $this->assertSame($admin->id, $reference->created_by);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'created',
            'module' => 'client_references',
            'auditable_type' => ClientReference::class,
            'auditable_id' => $reference->id,
        ]);
    }

    public function test_only_one_reference_can_be_primary(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);

        $first = ClientReference::query()->create([
            'client_id' => $client->id,
            'type' => 'family',
            'full_name' => 'Primera Referencia',
            'phone' => '50255550001',
            'is_primary' => true,
        ]);

        $second = ClientReference::query()->create([
            'client_id' => $client->id,
            'type' => 'personal',
            'full_name' => 'Segunda Referencia',
            'phone' => '50255550002',
            'is_primary' => false,
        ]);

        $this->actingAs($admin)
            ->put("/clients/{$client->id}/references/{$second->id}", [
                'type' => 'personal',
                'full_name' => 'Segunda Referencia',
                'phone' => '50255550002',
                'is_primary' => '1',
            ])
            ->assertRedirect("/clients/{$client->id}/references");

        $this->assertFalse($first->fresh()->is_primary);
        $this->assertTrue($second->fresh()->is_primary);
    }

    public function test_reference_requires_numeric_phones(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);

        $this->actingAs($admin)
            ->post("/clients/{$client->id}/references", [
                'type' => 'family',
                'full_name' => 'Referencia Inválida',
                'relationship' => 'Hermano',
                'phone' => 'TEL555',
                'secondary_phone' => 'ABC123',
            ])
            ->assertSessionHasErrors(['phone', 'secondary_phone']);
    }

    public function test_admin_can_delete_reference(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);

        $reference = ClientReference::query()->create([
            'client_id' => $client->id,
            'type' => 'personal',
            'full_name' => 'Referencia Temporal',
            'phone' => '50255553333',
        ]);

        $this->actingAs($admin)
            ->delete("/clients/{$client->id}/references/{$reference->id}")
            ->assertRedirect("/clients/{$client->id}/references");

        $this->assertSoftDeleted('client_references', [
            'id' => $reference->id,
        ]);
    }

    public function test_reference_from_other_client_returns_404(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);

        $otherClient = Client::query()->create([
            'agency_id' => $admin->agency_id,
            'code' => 'CLI-000011-OTHER',
            'first_name' => 'Otro',
            'last_name' => 'Cliente',
            'dpi' => '1234567890111',
            'phone' => '50255550011',
            'address_line' => 'Zona 11',
            'country' => 'Guatemala',
            'status' => 'active',
        ]);

        $reference = ClientReference::query()->create([
            'client_id' => $otherClient->id,
            'type' => 'personal',
            'full_name' => 'Referencia Ajena',
            'phone' => '50255554444',
        ]);

        $this->actingAs($admin)
            ->get("/clients/{$client->id}/references/{$reference->id}/edit")
            ->assertStatus(404);
    }

    public function test_guest_cannot_access_client_references(): void
    {
        $this->get('/clients/1/references')
            ->assertRedirect('/login');
    }
}
