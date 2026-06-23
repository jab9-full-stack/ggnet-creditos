<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Client;
use App\Models\Credit;
use App\Models\CreditRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreditManagementTest extends TestCase
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
            'credits.view',
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
            'name' => 'Admin Créditos',
            'email' => 'admin-credits@example.com',
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
            'code' => 'CLI-000060-CREDIT',
            'first_name' => 'Cliente',
            'last_name' => 'Crédito',
            'dpi' => '1234567890160',
            'phone' => '50255550060',
            'address_line' => 'Zona 60',
            'country' => 'Guatemala',
            'status' => 'active',
        ]);
    }

    private function credit(User $admin, Client $client): Credit
    {
        $creditRequest = CreditRequest::query()->create([
            'agency_id' => $admin->agency_id,
            'client_id' => $client->id,
            'code' => 'SOL-CREDIT-001',
            'status' => CreditRequest::STATUS_APPROVED,
            'requested_amount' => 1800,
            'requested_term_weeks' => 4,
            'approved_at' => now(),
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
        ]);

        return Credit::query()->create([
            'agency_id' => $admin->agency_id,
            'client_id' => $client->id,
            'credit_request_id' => $creditRequest->id,
            'code' => 'CRE-000001',
            'status' => Credit::STATUS_APPROVED_PENDING_DISBURSEMENT,
            'principal_amount' => 1800,
            'interest_rate_percent' => 25,
            'interest_amount' => 450,
            'total_amount' => 2250,
            'term_weeks' => 4,
            'approved_at' => now(),
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
        ]);
    }

    public function test_admin_can_view_credits_index(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);
        $credit = $this->credit($admin, $client);

        $this->actingAs($admin)
            ->get('/credits')
            ->assertStatus(200)
            ->assertSee('Créditos')
            ->assertSee($credit->code)
            ->assertSee('Cliente Crédito')
            ->assertSee('Aprobado pendiente de entrega');
    }

    public function test_admin_can_view_credit_detail(): void
    {
        $admin = $this->admin();
        $client = $this->client($admin);
        $credit = $this->credit($admin, $client);

        $this->actingAs($admin)
            ->get("/credits/{$credit->id}")
            ->assertStatus(200)
            ->assertSee('Detalle de crédito')
            ->assertSee($credit->code)
            ->assertSee('Q 1,800.00')
            ->assertSee('Q 2,250.00')
            ->assertSee('25.00%');
    }

    public function test_guest_cannot_access_credits(): void
    {
        $this->get('/credits')
            ->assertRedirect('/login');
    }
}
