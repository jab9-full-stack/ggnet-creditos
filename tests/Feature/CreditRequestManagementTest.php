<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Client;
use App\Models\CreditRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreditRequestManagementTest extends TestCase
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
            'credit_requests.view',
            'credit_requests.create',
            'credit_requests.update',
            'credit_requests.delete',
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
            'name' => 'Admin Solicitudes',
            'email' => 'admin-credit-requests@example.com',
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
            'code' => 'CLI-000040-CRED',
            'first_name' => 'Cliente',
            'last_name' => 'Crédito',
            'dpi' => '1234567890140',
            'phone' => '50255550040',
            'address_line' => 'Zona 40',
            'country' => 'Guatemala',
            'occupation' => 'Comerciante',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_credit_requests_index(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);

        CreditRequest::query()->create([
            'agency_id' => $admin->agency_id,
            'client_id' => $client->id,
            'code' => 'SOL-000001',
            'status' => CreditRequest::STATUS_DRAFT,
            'requested_amount' => 1500,
            'requested_term_weeks' => 4,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get('/credit-requests')
            ->assertStatus(200)
            ->assertSee('Solicitudes de crédito')
            ->assertSee('SOL-000001')
            ->assertSee('Cliente Crédito');
    }

    public function test_admin_can_create_credit_request(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);

        $this->actingAs($admin)
            ->post('/credit-requests', [
                'client_id' => $client->id,
                'agency_id' => $admin->agency_id,
                'requested_amount' => '1800.00',
                'requested_term_weeks' => '4',
                'purpose' => 'Capital de trabajo',
                'income_source' => 'Negocio propio',
                'monthly_income' => '3500.00',
                'notes' => 'Solicitud inicial',
            ])
            ->assertRedirect();

        $creditRequest = CreditRequest::query()->where('client_id', $client->id)->firstOrFail();

        $this->assertSame('draft', $creditRequest->status);
        $this->assertSame($admin->id, $creditRequest->created_by);
        $this->assertSame('SOL-000001', $creditRequest->code);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'created',
            'module' => 'credit_requests',
            'auditable_type' => CreditRequest::class,
            'auditable_id' => $creditRequest->id,
        ]);
    }

    public function test_admin_can_update_draft_credit_request(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);

        $creditRequest = CreditRequest::query()->create([
            'agency_id' => $admin->agency_id,
            'client_id' => $client->id,
            'code' => 'SOL-000002',
            'status' => CreditRequest::STATUS_DRAFT,
            'requested_amount' => 1000,
            'requested_term_weeks' => 4,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->put("/credit-requests/{$creditRequest->id}", [
                'client_id' => $client->id,
                'agency_id' => $admin->agency_id,
                'requested_amount' => '2000.00',
                'requested_term_weeks' => '6',
                'purpose' => 'Inventario',
                'income_source' => 'Ventas',
                'monthly_income' => '4000.00',
                'notes' => 'Ajuste de monto',
            ])
            ->assertRedirect("/credit-requests/{$creditRequest->id}");

        $creditRequest->refresh();

        $this->assertSame('2000.00', $creditRequest->requested_amount);
        $this->assertSame(6, $creditRequest->requested_term_weeks);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'updated',
            'module' => 'credit_requests',
            'auditable_type' => CreditRequest::class,
            'auditable_id' => $creditRequest->id,
        ]);
    }

    public function test_approved_credit_request_cannot_be_updated_from_m03_1(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);

        $creditRequest = CreditRequest::query()->create([
            'agency_id' => $admin->agency_id,
            'client_id' => $client->id,
            'code' => 'SOL-000003',
            'status' => CreditRequest::STATUS_APPROVED,
            'requested_amount' => 1000,
            'requested_term_weeks' => 4,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->put("/credit-requests/{$creditRequest->id}", [
                'client_id' => $client->id,
                'agency_id' => $admin->agency_id,
                'requested_amount' => '3000.00',
                'requested_term_weeks' => '8',
            ])
            ->assertRedirect("/credit-requests/{$creditRequest->id}");

        $this->assertSame('1000.00', $creditRequest->fresh()->requested_amount);
    }

    public function test_admin_can_filter_credit_requests_by_status(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);

        CreditRequest::query()->create([
            'agency_id' => $admin->agency_id,
            'client_id' => $client->id,
            'code' => 'SOL-000004',
            'status' => CreditRequest::STATUS_DRAFT,
            'requested_amount' => 1000,
            'created_by' => $admin->id,
        ]);

        CreditRequest::query()->create([
            'agency_id' => $admin->agency_id,
            'client_id' => $client->id,
            'code' => 'SOL-000005',
            'status' => CreditRequest::STATUS_REJECTED,
            'requested_amount' => 1200,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get('/credit-requests?status=rejected')
            ->assertStatus(200)
            ->assertSee('SOL-000005')
            ->assertDontSee('SOL-000004');
    }

    public function test_admin_can_view_credit_request_detail(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);

        $creditRequest = CreditRequest::query()->create([
            'agency_id' => $admin->agency_id,
            'client_id' => $client->id,
            'code' => 'SOL-000006',
            'status' => CreditRequest::STATUS_DRAFT,
            'requested_amount' => 1500,
            'requested_term_weeks' => 4,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get("/credit-requests/{$creditRequest->id}")
            ->assertStatus(200)
            ->assertSee('Detalle de solicitud')
            ->assertSee('SOL-000006')
            ->assertSee('Cliente Crédito');
    }

    public function test_admin_can_soft_delete_draft_credit_request(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);

        $creditRequest = CreditRequest::query()->create([
            'agency_id' => $admin->agency_id,
            'client_id' => $client->id,
            'code' => 'SOL-000007',
            'status' => CreditRequest::STATUS_DRAFT,
            'requested_amount' => 1500,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->delete("/credit-requests/{$creditRequest->id}")
            ->assertRedirect('/credit-requests');

        $this->assertSoftDeleted('credit_requests', [
            'id' => $creditRequest->id,
        ]);
    }

    public function test_guest_cannot_access_credit_requests(): void
    {
        $this->get('/credit-requests')
            ->assertRedirect('/login');
    }
}
