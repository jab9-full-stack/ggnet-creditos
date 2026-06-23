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

class CreditRequestStatusManagementTest extends TestCase
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
            'credit_requests.review',
            'credit_requests.approve',
            'credit_requests.reject',
            'credit_requests.delete',
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
            'name' => 'Admin Estados',
            'email' => 'admin-status@example.com',
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
            'code' => 'CLI-000050-STATUS',
            'first_name' => 'Cliente',
            'last_name' => 'Estados',
            'dpi' => '1234567890150',
            'phone' => '50255550050',
            'address_line' => 'Zona 50',
            'country' => 'Guatemala',
            'status' => 'active',
        ]);
    }

    private function creditRequest(User $admin, Client $client, string $status = CreditRequest::STATUS_DRAFT): CreditRequest
    {
        return CreditRequest::query()->create([
            'agency_id' => $admin->agency_id,
            'client_id' => $client->id,
            'code' => 'SOL-STATUS-'.$status,
            'status' => $status,
            'requested_amount' => 1500,
            'requested_term_weeks' => 4,
            'created_by' => $admin->id,
        ]);
    }

    public function test_draft_credit_request_can_be_submitted(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);
        $creditRequest = $this->creditRequest($admin, $client);

        $this->actingAs($admin)
            ->post("/credit-requests/{$creditRequest->id}/submit")
            ->assertRedirect("/credit-requests/{$creditRequest->id}");

        $creditRequest->refresh();

        $this->assertSame(CreditRequest::STATUS_SUBMITTED, $creditRequest->status);
        $this->assertNotNull($creditRequest->submitted_at);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'credit_request.submitted',
            'module' => 'credit_requests',
            'auditable_type' => CreditRequest::class,
            'auditable_id' => $creditRequest->id,
        ]);
    }

    public function test_submitted_credit_request_can_start_review(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);
        $creditRequest = $this->creditRequest($admin, $client, CreditRequest::STATUS_SUBMITTED);

        $this->actingAs($admin)
            ->post("/credit-requests/{$creditRequest->id}/start-review")
            ->assertRedirect("/credit-requests/{$creditRequest->id}");

        $creditRequest->refresh();

        $this->assertSame(CreditRequest::STATUS_IN_REVIEW, $creditRequest->status);
        $this->assertSame($admin->id, $creditRequest->reviewed_by);
        $this->assertNotNull($creditRequest->reviewed_at);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'credit_request.review_started',
            'module' => 'credit_requests',
            'auditable_type' => CreditRequest::class,
            'auditable_id' => $creditRequest->id,
        ]);
    }

    public function test_in_review_credit_request_can_be_approved_without_creating_credit(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);
        $creditRequest = $this->creditRequest($admin, $client, CreditRequest::STATUS_IN_REVIEW);

        $this->actingAs($admin)
            ->post("/credit-requests/{$creditRequest->id}/approve")
            ->assertRedirect("/credit-requests/{$creditRequest->id}");

        $creditRequest->refresh();

        $this->assertSame(CreditRequest::STATUS_APPROVED, $creditRequest->status);
        $this->assertSame($admin->id, $creditRequest->approved_by);
        $this->assertNotNull($creditRequest->approved_at);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'credit_request.approved',
            'module' => 'credit_requests',
            'auditable_type' => CreditRequest::class,
            'auditable_id' => $creditRequest->id,
        ]);
    }

    public function test_in_review_credit_request_can_be_rejected(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);
        $creditRequest = $this->creditRequest($admin, $client, CreditRequest::STATUS_IN_REVIEW);

        $this->actingAs($admin)
            ->post("/credit-requests/{$creditRequest->id}/reject")
            ->assertRedirect("/credit-requests/{$creditRequest->id}");

        $creditRequest->refresh();

        $this->assertSame(CreditRequest::STATUS_REJECTED, $creditRequest->status);
        $this->assertSame($admin->id, $creditRequest->rejected_by);
        $this->assertNotNull($creditRequest->rejected_at);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'credit_request.rejected',
            'module' => 'credit_requests',
            'auditable_type' => CreditRequest::class,
            'auditable_id' => $creditRequest->id,
        ]);
    }

    public function test_credit_request_can_be_cancelled_before_final_status(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);
        $creditRequest = $this->creditRequest($admin, $client, CreditRequest::STATUS_SUBMITTED);

        $this->actingAs($admin)
            ->post("/credit-requests/{$creditRequest->id}/cancel")
            ->assertRedirect("/credit-requests/{$creditRequest->id}");

        $creditRequest->refresh();

        $this->assertSame(CreditRequest::STATUS_CANCELLED, $creditRequest->status);
        $this->assertSame($admin->id, $creditRequest->cancelled_by);
        $this->assertNotNull($creditRequest->cancelled_at);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'credit_request.cancelled',
            'module' => 'credit_requests',
            'auditable_type' => CreditRequest::class,
            'auditable_id' => $creditRequest->id,
        ]);
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);
        $creditRequest = $this->creditRequest($admin, $client, CreditRequest::STATUS_DRAFT);

        $this->actingAs($admin)
            ->post("/credit-requests/{$creditRequest->id}/approve")
            ->assertRedirect("/credit-requests/{$creditRequest->id}");

        $this->assertSame(CreditRequest::STATUS_DRAFT, $creditRequest->fresh()->status);
    }

    public function test_final_status_cannot_be_cancelled(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);
        $creditRequest = $this->creditRequest($admin, $client, CreditRequest::STATUS_APPROVED);

        $this->actingAs($admin)
            ->post("/credit-requests/{$creditRequest->id}/cancel")
            ->assertRedirect("/credit-requests/{$creditRequest->id}");

        $this->assertSame(CreditRequest::STATUS_APPROVED, $creditRequest->fresh()->status);
    }

    public function test_status_history_displays_statuses_in_spanish(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);
        $creditRequest = $this->creditRequest($admin, $client);

        $this->actingAs($admin)
            ->post("/credit-requests/{$creditRequest->id}/submit")
            ->assertRedirect("/credit-requests/{$creditRequest->id}");

        $this->actingAs($admin)
            ->get("/credit-requests/{$creditRequest->id}")
            ->assertStatus(200)
            ->assertSee('Borrador')
            ->assertSee('Enviada');
    }

    public function test_final_status_message_explains_credit_generation_is_pending(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);
        $creditRequest = $this->creditRequest($admin, $client, CreditRequest::STATUS_APPROVED);

        $this->actingAs($admin)
            ->get("/credit-requests/{$creditRequest->id}")
            ->assertStatus(200)
            ->assertSee('Solicitud aprobada. Pendiente de generar crédito en módulo posterior.');
    }

    public function test_guest_cannot_change_credit_request_status(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);
        $creditRequest = $this->creditRequest($admin, $client);

        auth()->logout();

        $this->post("/credit-requests/{$creditRequest->id}/submit")
            ->assertRedirect('/login');
    }
}
