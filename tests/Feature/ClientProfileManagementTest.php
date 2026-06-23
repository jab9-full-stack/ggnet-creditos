<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\ClientReference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClientProfileManagementTest extends TestCase
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
            'clients.update',
            'client_references.view',
            'client_references.create',
            'client_documents.view',
            'client_documents.create',
            'client_documents.download',
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
            'name' => 'Admin Expediente',
            'email' => 'admin-profile@example.com',
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
            'code' => 'CLI-000030-PROFILE',
            'first_name' => 'Cliente',
            'last_name' => 'Perfil',
            'dpi' => '1234567890130',
            'phone' => '50255550030',
            'address_line' => 'Zona 30',
            'country' => 'Guatemala',
            'occupation' => 'Comerciante',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_client_profile(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);

        ClientReference::query()->create([
            'client_id' => $client->id,
            'type' => 'family',
            'full_name' => 'Referencia Perfil',
            'relationship' => 'Madre',
            'phone' => '50255551130',
            'is_primary' => true,
        ]);

        ClientDocument::query()->create([
            'client_id' => $client->id,
            'type' => 'dpi_front',
            'title' => 'DPI frontal',
            'original_name' => 'dpi-front.pdf',
            'disk' => 'local',
            'path' => "clients/{$client->id}/documents/dpi-front.pdf",
            'mime_type' => 'application/pdf',
            'size_bytes' => 128,
            'status' => 'verified',
            'uploaded_by' => $admin->id,
            'verified_by' => $admin->id,
            'verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get("/clients/{$client->id}")
            ->assertStatus(200)
            ->assertSee('Expediente del cliente')
            ->assertSee('Cliente Perfil')
            ->assertSee('Checklist del expediente')
            ->assertSee('Referencia Perfil')
            ->assertSee('DPI frontal');
    }

    public function test_client_profile_shows_pending_checklist_items(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);

        $this->actingAs($admin)
            ->get("/clients/{$client->id}")
            ->assertStatus(200)
            ->assertSee('Referencias registradas')
            ->assertSee('DPI frontal cargado')
            ->assertSee('DPI reverso cargado')
            ->assertSee('Documento verificado');
    }

    public function test_guest_cannot_access_client_profile(): void
    {
        $this->get('/clients/1')
            ->assertRedirect('/login');
    }
}
