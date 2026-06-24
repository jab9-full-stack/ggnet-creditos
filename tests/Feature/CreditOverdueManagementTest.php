<?php

namespace Tests\Feature;

use App\Models\CashSession;
use App\Models\Agency;
use App\Models\Client;
use App\Models\Credit;
use App\Models\CreditInstallment;
use App\Models\CreditPayment;
use App\Models\CreditRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreditOverdueManagementTest extends TestCase
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
            'credit_payments.view',
            'credit_payments.create',
            'credit_installments.mark_overdue',
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
            'name' => 'Admin Mora',
            'email' => 'admin-overdue@example.com',
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
            'code' => 'CLI-000090-OVERDUE',
            'first_name' => 'Cliente',
            'last_name' => 'Mora',
            'dpi' => '1234567890190',
            'phone' => '50255550090',
            'address_line' => 'Zona 90',
            'country' => 'Guatemala',
            'status' => 'active',
        ]);
    }

    private function disbursedCredit(User $admin, Client $client): Credit
    {
        $creditRequest = CreditRequest::query()->create([
            'agency_id' => $admin->agency_id,
            'client_id' => $client->id,
            'code' => 'SOL-OVERDUE-001',
            'status' => CreditRequest::STATUS_APPROVED,
            'requested_amount' => 1800,
            'requested_term_weeks' => 4,
            'approved_at' => Carbon::parse('2026-06-01 15:00:00'),
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
        ]);

        $credit = Credit::query()->create([
            'agency_id' => $admin->agency_id,
            'client_id' => $client->id,
            'credit_request_id' => $creditRequest->id,
            'code' => 'CRE-OVERDUE-001',
            'status' => Credit::STATUS_DISBURSED,
            'principal_amount' => 1800,
            'interest_rate_percent' => 25,
            'interest_amount' => 450,
            'total_amount' => 2250,
            'term_weeks' => 4,
            'approved_at' => Carbon::parse('2026-06-01 15:00:00'),
            'disbursed_at' => Carbon::parse('2026-06-02 10:00:00'),
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
            'disbursed_by' => $admin->id,
        ]);

        foreach ([1, 2, 3, 4] as $number) {
            CreditInstallment::query()->create([
                'agency_id' => $admin->agency_id,
                'client_id' => $client->id,
                'credit_id' => $credit->id,
                'number' => $number,
                'status' => CreditInstallment::STATUS_PENDING,
                'due_date' => Carbon::parse('2026-06-02')->addWeeks($number)->toDateString(),
                'principal_amount' => 450,
                'interest_amount' => 112.50,
                'total_amount' => 562.50,
                'paid_amount' => 0,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);
        }

        return $credit;
    }

    private function openCashSession(User $admin): CashSession
    {
        return CashSession::query()->create([
            'agency_id' => $admin->agency_id,
            'user_id' => $admin->id,
            'code' => 'CAJ-TEST-'.uniqid(),
            'status' => CashSession::STATUS_OPEN,
            'opening_balance' => '0.00',
            'opened_at' => now(),
            'opened_by' => $admin->id,
            'expected_cash_amount' => '0.00',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }


    public function test_admin_can_mark_credit_installments_as_overdue(): void
    {
        Carbon::setTestNow('2026-06-17 08:00:00');

        $admin = $this->admin();
        $client = $this->client($admin);
        $credit = $this->disbursedCredit($admin, $client);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/mark-overdue")
            ->assertRedirect("/credits/{$credit->id}");

        $firstInstallment = CreditInstallment::query()
            ->where('credit_id', $credit->id)
            ->where('number', 1)
            ->firstOrFail();

        $secondInstallment = CreditInstallment::query()
            ->where('credit_id', $credit->id)
            ->where('number', 2)
            ->firstOrFail();

        $thirdInstallment = CreditInstallment::query()
            ->where('credit_id', $credit->id)
            ->where('number', 3)
            ->firstOrFail();

        $this->assertSame(CreditInstallment::STATUS_OVERDUE, $firstInstallment->status);
        $this->assertSame(CreditInstallment::STATUS_OVERDUE, $secondInstallment->status);
        $this->assertNotNull($firstInstallment->overdue_at);
        $this->assertSame(CreditInstallment::STATUS_PENDING, $thirdInstallment->status);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'credit_installment.marked_overdue',
            'module' => 'credit_installments',
            'auditable_type' => CreditInstallment::class,
            'auditable_id' => $firstInstallment->id,
        ]);
    }

    public function test_command_can_mark_overdue_installments(): void
    {
        Carbon::setTestNow('2026-06-17 08:00:00');

        $admin = $this->admin();
        $client = $this->client($admin);
        $credit = $this->disbursedCredit($admin, $client);

        $this->artisan('credits:mark-overdue-installments', [
            '--credit_id' => $credit->id,
        ])->assertExitCode(0);

        $this->assertSame(2, CreditInstallment::query()
            ->where('credit_id', $credit->id)
            ->where('status', CreditInstallment::STATUS_OVERDUE)
            ->count());
    }

    public function test_payment_pays_overdue_installments_before_pending_installments(): void
    {
        Carbon::setTestNow('2026-06-17 08:00:00');

        $admin = $this->admin();
        $client = $this->client($admin);
        $credit = $this->disbursedCredit($admin, $client);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/mark-overdue")
            ->assertRedirect("/credits/{$credit->id}");

        $this->openCashSession($admin);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/payments", [
                'installments_count' => 1,
                'payment_method' => CreditPayment::METHOD_CASH,
                'payment_date' => '2026-06-17',
            ])
            ->assertRedirect("/credits/{$credit->id}");

        $firstInstallment = CreditInstallment::query()
            ->where('credit_id', $credit->id)
            ->where('number', 1)
            ->firstOrFail();

        $secondInstallment = CreditInstallment::query()
            ->where('credit_id', $credit->id)
            ->where('number', 2)
            ->firstOrFail();

        $this->assertSame(CreditInstallment::STATUS_PAID, $firstInstallment->status);
        $this->assertSame('562.50', $firstInstallment->paid_amount);
        $this->assertSame(CreditInstallment::STATUS_OVERDUE, $secondInstallment->status);
    }

    public function test_credit_detail_displays_overdue_summary(): void
    {
        Carbon::setTestNow('2026-06-17 08:00:00');

        $admin = $this->admin();
        $client = $this->client($admin);
        $credit = $this->disbursedCredit($admin, $client);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/mark-overdue")
            ->assertRedirect("/credits/{$credit->id}");

        $this->actingAs($admin)
            ->get("/credits/{$credit->id}")
            ->assertStatus(200)
            ->assertSee('Cuotas vencidas')
            ->assertSee('Saldo vencido')
            ->assertSee('No se aplican recargos');
    }

    public function test_guest_cannot_mark_overdue(): void
    {
        Carbon::setTestNow('2026-06-17 08:00:00');

        $admin = $this->admin();
        $client = $this->client($admin);
        $credit = $this->disbursedCredit($admin, $client);

        auth()->logout();

        $this->post("/credits/{$credit->id}/mark-overdue")
            ->assertRedirect('/login');
    }
}
