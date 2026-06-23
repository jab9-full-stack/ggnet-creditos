<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Client;
use App\Models\Credit;
use App\Models\CreditInstallment;
use App\Models\CreditRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreditDisbursementManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

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
            'credits.disburse',
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
            'name' => 'Admin Desembolso',
            'email' => 'admin-disbursement@example.com',
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
            'code' => 'CLI-000070-DISB',
            'first_name' => 'Cliente',
            'last_name' => 'Desembolso',
            'dpi' => '1234567890170',
            'phone' => '50255550070',
            'address_line' => 'Zona 70',
            'country' => 'Guatemala',
            'status' => 'active',
        ]);
    }

    private function credit(User $admin, Client $client): Credit
    {
        $creditRequest = CreditRequest::query()->create([
            'agency_id' => $admin->agency_id,
            'client_id' => $client->id,
            'code' => 'SOL-DISB-001',
            'status' => CreditRequest::STATUS_APPROVED,
            'requested_amount' => 1800,
            'requested_term_weeks' => 4,
            'approved_at' => Carbon::parse('2026-06-23 15:00:00'),
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
        ]);

        return Credit::query()->create([
            'agency_id' => $admin->agency_id,
            'client_id' => $client->id,
            'credit_request_id' => $creditRequest->id,
            'code' => 'CRE-DISB-001',
            'status' => Credit::STATUS_APPROVED_PENDING_DISBURSEMENT,
            'principal_amount' => 1800,
            'interest_rate_percent' => 25,
            'interest_amount' => 450,
            'total_amount' => 2250,
            'term_weeks' => 4,
            'approved_at' => Carbon::parse('2026-06-23 15:00:00'),
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
        ]);
    }

    public function test_admin_can_disburse_credit_and_generate_installments(): void
    {
        Carbon::setTestNow('2026-06-24 10:00:00');

        $admin = $this->admin();
        $client = $this->client($admin);
        $credit = $this->credit($admin, $client);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/disburse", [
                'disbursement_date' => '2026-06-24',
            ])
            ->assertRedirect("/credits/{$credit->id}");

        $credit->refresh();

        $this->assertSame(Credit::STATUS_DISBURSED, $credit->status);
        $this->assertSame($admin->id, $credit->disbursed_by);
        $this->assertNotNull($credit->disbursed_at);

        $this->assertSame(4, $credit->installments()->count());

        $this->assertDatabaseHas('credit_installments', [
            'credit_id' => $credit->id,
            'number' => 1,
            'due_date' => '2026-07-01',
            'principal_amount' => '450.00',
            'interest_amount' => '112.50',
            'total_amount' => '562.50',
            'status' => CreditInstallment::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('credit_installments', [
            'credit_id' => $credit->id,
            'number' => 4,
            'due_date' => '2026-07-22',
            'principal_amount' => '450.00',
            'interest_amount' => '112.50',
            'total_amount' => '562.50',
            'status' => CreditInstallment::STATUS_PENDING,
        ]);

        $totalInstallments = CreditInstallment::query()
            ->where('credit_id', $credit->id)
            ->sum('total_amount');

        $this->assertSame('2250.00', number_format((float) $totalInstallments, 2, '.', ''));

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'credit.disbursed',
            'module' => 'credits',
            'auditable_type' => Credit::class,
            'auditable_id' => $credit->id,
        ]);
    }

    public function test_credit_cannot_be_disbursed_twice(): void
    {
        Carbon::setTestNow('2026-06-24 10:00:00');

        $admin = $this->admin();
        $client = $this->client($admin);
        $credit = $this->credit($admin, $client);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/disburse", [
                'disbursement_date' => '2026-06-24',
            ])
            ->assertRedirect("/credits/{$credit->id}");

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/disburse", [
                'disbursement_date' => '2026-06-24',
            ])
            ->assertRedirect("/credits/{$credit->id}");

        $this->assertSame(4, $credit->fresh()->installments()->count());
    }

    public function test_disbursement_date_cannot_be_before_approval(): void
    {
        Carbon::setTestNow('2026-06-24 10:00:00');

        $admin = $this->admin();
        $client = $this->client($admin);
        $credit = $this->credit($admin, $client);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/disburse", [
                'disbursement_date' => '2026-06-22',
            ])
            ->assertSessionHasErrors('disbursement_date');

        $this->assertSame(Credit::STATUS_APPROVED_PENDING_DISBURSEMENT, $credit->fresh()->status);
        $this->assertSame(0, $credit->fresh()->installments()->count());
    }

    public function test_credit_detail_displays_generated_installments(): void
    {
        Carbon::setTestNow('2026-06-24 10:00:00');

        $admin = $this->admin();
        $client = $this->client($admin);
        $credit = $this->credit($admin, $client);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/disburse", [
                'disbursement_date' => '2026-06-24',
            ]);

        $this->actingAs($admin)
            ->get("/credits/{$credit->id}")
            ->assertStatus(200)
            ->assertSee('Calendario de cuotas')
            ->assertSee('Cuota')
            ->assertSee('562.50')
            ->assertSee('Entregado');
    }

    public function test_guest_cannot_disburse_credit(): void
    {
        Carbon::setTestNow('2026-06-24 10:00:00');

        $admin = $this->admin();
        $client = $this->client($admin);
        $credit = $this->credit($admin, $client);

        auth()->logout();

        $this->post("/credits/{$credit->id}/disburse", [
            'disbursement_date' => '2026-06-24',
        ])->assertRedirect('/login');
    }
}
