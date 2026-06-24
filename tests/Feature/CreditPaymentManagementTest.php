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

class CreditPaymentManagementTest extends TestCase
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
            'name' => 'Admin Pagos',
            'email' => 'admin-payments@example.com',
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
            'code' => 'CLI-000080-PAY',
            'first_name' => 'Cliente',
            'last_name' => 'Pago',
            'dpi' => '1234567890180',
            'phone' => '50255550080',
            'address_line' => 'Zona 80',
            'country' => 'Guatemala',
            'status' => 'active',
        ]);
    }

    private function disbursedCredit(User $admin, Client $client): Credit
    {
        $creditRequest = CreditRequest::query()->create([
            'agency_id' => $admin->agency_id,
            'client_id' => $client->id,
            'code' => 'SOL-PAY-001',
            'status' => CreditRequest::STATUS_APPROVED,
            'requested_amount' => 1800,
            'requested_term_weeks' => 4,
            'approved_at' => Carbon::parse('2026-06-23 15:00:00'),
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
        ]);

        $credit = Credit::query()->create([
            'agency_id' => $admin->agency_id,
            'client_id' => $client->id,
            'credit_request_id' => $creditRequest->id,
            'code' => 'CRE-PAY-001',
            'status' => Credit::STATUS_DISBURSED,
            'principal_amount' => 1800,
            'interest_rate_percent' => 25,
            'interest_amount' => 450,
            'total_amount' => 2250,
            'term_weeks' => 4,
            'approved_at' => Carbon::parse('2026-06-23 15:00:00'),
            'disbursed_at' => Carbon::parse('2026-06-24 10:00:00'),
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
                'due_date' => Carbon::parse('2026-06-24')->addWeeks($number)->toDateString(),
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


    public function test_admin_can_register_one_full_installment_payment(): void
    {
        Carbon::setTestNow('2026-07-01 11:00:00');

        $admin = $this->admin();
        $client = $this->client($admin);
        $credit = $this->disbursedCredit($admin, $client);

        $this->openCashSession($admin);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/payments", [
                'installments_count' => 1,
                'payment_method' => CreditPayment::METHOD_CASH,
                'payment_date' => '2026-07-01',
            ])
            ->assertRedirect("/credits/{$credit->id}");

        $payment = CreditPayment::query()->where('credit_id', $credit->id)->firstOrFail();

        $this->assertSame('562.50', $payment->amount);
        $this->assertSame(1, $payment->installments_count);

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
        $this->assertSame($payment->id, $firstInstallment->credit_payment_id);

        $this->assertSame(CreditInstallment::STATUS_PENDING, $secondInstallment->status);
        $this->assertSame(Credit::STATUS_DISBURSED, $credit->fresh()->status);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'credit_payment.created',
            'module' => 'credit_payments',
            'auditable_type' => CreditPayment::class,
            'auditable_id' => $payment->id,
        ]);
    }

    public function test_admin_can_pay_all_pending_installments_and_close_credit(): void
    {
        Carbon::setTestNow('2026-07-01 11:00:00');

        $admin = $this->admin();
        $client = $this->client($admin);
        $credit = $this->disbursedCredit($admin, $client);

        $this->openCashSession($admin);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/payments", [
                'installments_count' => 4,
                'payment_method' => CreditPayment::METHOD_CASH,
                'payment_date' => '2026-07-01',
            ])
            ->assertRedirect("/credits/{$credit->id}");

        $payment = CreditPayment::query()->where('credit_id', $credit->id)->firstOrFail();

        $this->assertSame('2250.00', $payment->amount);
        $this->assertSame(4, $payment->installments_count);
        $this->assertSame(4, $payment->installments()->count());
        $this->assertSame(0, $credit->fresh()->installments()->where('status', CreditInstallment::STATUS_PENDING)->count());
        $this->assertSame(Credit::STATUS_CLOSED, $credit->fresh()->status);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'credit.closed',
            'module' => 'credits',
            'auditable_type' => Credit::class,
            'auditable_id' => $credit->id,
        ]);
    }

    public function test_deposit_or_transfer_requires_reference(): void
    {
        Carbon::setTestNow('2026-07-01 11:00:00');

        $admin = $this->admin();
        $client = $this->client($admin);
        $credit = $this->disbursedCredit($admin, $client);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/payments", [
                'installments_count' => 1,
                'payment_method' => CreditPayment::METHOD_DEPOSIT,
                'payment_date' => '2026-07-01',
            ])
            ->assertSessionHasErrors('reference');

        $this->assertSame(0, CreditPayment::query()->count());
        $this->assertSame(4, $credit->fresh()->installments()->where('status', CreditInstallment::STATUS_PENDING)->count());
    }

    public function test_cannot_pay_more_installments_than_pending(): void
    {
        Carbon::setTestNow('2026-07-01 11:00:00');

        $admin = $this->admin();
        $client = $this->client($admin);
        $credit = $this->disbursedCredit($admin, $client);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/payments", [
                'installments_count' => 5,
                'payment_method' => CreditPayment::METHOD_CASH,
                'payment_date' => '2026-07-01',
            ])
            ->assertSessionHasErrors('installments_count');

        $this->assertSame(0, CreditPayment::query()->count());
    }

    public function test_credit_detail_displays_payment_form_and_history(): void
    {
        Carbon::setTestNow('2026-07-01 11:00:00');

        $admin = $this->admin();
        $client = $this->client($admin);
        $credit = $this->disbursedCredit($admin, $client);

        $this->actingAs($admin)
            ->get("/credits/{$credit->id}")
            ->assertStatus(200)
            ->assertSee('Registrar pago completo')
            ->assertSee('Pagos registrados')
            ->assertSee('No se aceptan pagos parciales');
    }

    public function test_guest_cannot_register_payment(): void
    {
        Carbon::setTestNow('2026-07-01 11:00:00');

        $admin = $this->admin();
        $client = $this->client($admin);
        $credit = $this->disbursedCredit($admin, $client);

        auth()->logout();

        $this->post("/credits/{$credit->id}/payments", [
            'installments_count' => 1,
            'payment_method' => CreditPayment::METHOD_CASH,
            'payment_date' => '2026-07-01',
        ])->assertRedirect('/login');
    }
}
