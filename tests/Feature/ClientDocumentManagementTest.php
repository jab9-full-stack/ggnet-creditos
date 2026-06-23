<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClientDocumentManagementTest extends TestCase
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
            'client_documents.view',
            'client_documents.create',
            'client_documents.update',
            'client_documents.delete',
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
            'name' => 'Admin Documentos',
            'email' => 'admin-documents@example.com',
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
            'code' => 'CLI-000020-DOC',
            'first_name' => 'Cliente',
            'last_name' => 'Documentos',
            'dpi' => '1234567890120',
            'phone' => '50255550020',
            'address_line' => 'Zona 20',
            'country' => 'Guatemala',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_client_documents(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);

        ClientDocument::query()->create([
            'client_id' => $client->id,
            'type' => 'dpi_front',
            'title' => 'DPI frontal',
            'original_name' => 'dpi.jpg',
            'disk' => 'local',
            'path' => 'clients/'.$client->id.'/documents/dpi.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 100,
            'status' => 'pending',
            'uploaded_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get("/clients/{$client->id}/documents")
            ->assertStatus(200)
            ->assertSee('Documentos')
            ->assertSee('DPI frontal');
    }

    public function test_admin_can_upload_document(): void
    {
        Storage::fake('local');

        $admin = $this->admin();
        $client = $this->client($admin);

        $this->actingAs($admin)
            ->post("/clients/{$client->id}/documents", [
                'type' => 'dpi_front',
                'title' => 'DPI frontal',
                'file' => UploadedFile::fake()->create('dpi.pdf', 128, 'application/pdf'),
                'status' => 'pending',
                'notes' => 'Documento inicial',
            ])
            ->assertRedirect("/clients/{$client->id}/documents");

        $document = ClientDocument::query()->where('title', 'DPI frontal')->firstOrFail();

        Storage::disk('local')->assertExists($document->path);

        $this->assertSame('dpi.pdf', $document->original_name);
        $this->assertSame($admin->id, $document->uploaded_by);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'created',
            'module' => 'client_documents',
            'auditable_type' => ClientDocument::class,
            'auditable_id' => $document->id,
        ]);
    }

    public function test_admin_can_update_document_status(): void
    {
        Storage::fake('local');

        $admin = $this->admin();
        $client = $this->client($admin);

        Storage::disk('local')->put("clients/{$client->id}/documents/file.pdf", 'contenido');

        $document = ClientDocument::query()->create([
            'client_id' => $client->id,
            'type' => 'other',
            'title' => 'Documento pendiente',
            'original_name' => 'file.pdf',
            'disk' => 'local',
            'path' => "clients/{$client->id}/documents/file.pdf",
            'mime_type' => 'application/pdf',
            'size_bytes' => 9,
            'status' => 'pending',
            'uploaded_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->put("/clients/{$client->id}/documents/{$document->id}", [
                'type' => 'other',
                'title' => 'Documento verificado',
                'status' => 'verified',
                'notes' => 'Correcto',
            ])
            ->assertRedirect("/clients/{$client->id}/documents");

        $document->refresh();

        $this->assertSame('verified', $document->status);
        $this->assertSame($admin->id, $document->verified_by);
        $this->assertNotNull($document->verified_at);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'updated',
            'module' => 'client_documents',
            'auditable_type' => ClientDocument::class,
            'auditable_id' => $document->id,
        ]);
    }

    public function test_document_upload_rejects_invalid_file_type(): void
    {
        Storage::fake('local');

        $admin = $this->admin();
        $client = $this->client($admin);

        $this->actingAs($admin)
            ->post("/clients/{$client->id}/documents", [
                'type' => 'other',
                'title' => 'Archivo inválido',
                'file' => UploadedFile::fake()->create('malware.exe', 10, 'application/x-msdownload'),
                'status' => 'pending',
            ])
            ->assertSessionHasErrors(['file']);
    }

    public function test_admin_can_download_document(): void
    {
        Storage::fake('local');

        $admin = $this->admin();
        $client = $this->client($admin);

        Storage::disk('local')->put("clients/{$client->id}/documents/test.pdf", 'contenido');

        $document = ClientDocument::query()->create([
            'client_id' => $client->id,
            'type' => 'other',
            'title' => 'PDF',
            'original_name' => 'test.pdf',
            'disk' => 'local',
            'path' => "clients/{$client->id}/documents/test.pdf",
            'mime_type' => 'application/pdf',
            'size_bytes' => 9,
            'status' => 'pending',
            'uploaded_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get("/clients/{$client->id}/documents/{$document->id}/download")
            ->assertStatus(200);
    }

    public function test_admin_can_soft_delete_document_without_removing_file(): void
    {
        Storage::fake('local');

        $admin = $this->admin();
        $client = $this->client($admin);

        Storage::disk('local')->put("clients/{$client->id}/documents/test.pdf", 'contenido');

        $document = ClientDocument::query()->create([
            'client_id' => $client->id,
            'type' => 'other',
            'title' => 'PDF',
            'original_name' => 'test.pdf',
            'disk' => 'local',
            'path' => "clients/{$client->id}/documents/test.pdf",
            'mime_type' => 'application/pdf',
            'size_bytes' => 9,
            'status' => 'pending',
            'uploaded_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->delete("/clients/{$client->id}/documents/{$document->id}")
            ->assertRedirect("/clients/{$client->id}/documents");

        $this->assertSoftDeleted('client_documents', [
            'id' => $document->id,
        ]);

        Storage::disk('local')->assertExists($document->path);
    }

    public function test_document_from_other_client_returns_404(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);

        $otherClient = Client::query()->create([
            'agency_id' => $admin->agency_id,
            'code' => 'CLI-000021-OTHER',
            'first_name' => 'Otro',
            'last_name' => 'Cliente',
            'dpi' => '1234567890121',
            'phone' => '50255550021',
            'address_line' => 'Zona 21',
            'country' => 'Guatemala',
            'status' => 'active',
        ]);

        $document = ClientDocument::query()->create([
            'client_id' => $otherClient->id,
            'type' => 'other',
            'title' => 'Ajeno',
            'original_name' => 'ajeno.pdf',
            'disk' => 'local',
            'path' => 'clients/'.$otherClient->id.'/documents/ajeno.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 9,
            'status' => 'pending',
            'uploaded_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get("/clients/{$client->id}/documents/{$document->id}/edit")
            ->assertStatus(404);
    }

    public function test_guest_cannot_access_client_documents(): void
    {
        $this->get('/clients/1/documents')
            ->assertRedirect('/login');
    }
}
