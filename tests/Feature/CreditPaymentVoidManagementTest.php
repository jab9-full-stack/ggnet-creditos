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

class CreditPaymentVoidManagementTest extends TestCase
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
            'credit_payments.void',
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
            'name' => 'Admin Anulaciones',
            'email' => 'admin-void@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
        ]);

        $user->assignRole($role);

        return $user;
    }

    private function disbursedCredit(User $admin): Credit
    {
        $client = Client::query()->create([
            'agency_id' => $admin->agency_id,
            'code' => 'CLI-000100-VOID',
            'first_name' => 'Cliente',
            'last_name' => 'Anulacion',
            'dpi' => '1234567890200',
            'phone' => '50255550100',
            'address_line' => 'Zona 100',
            'country' => 'Guatemala',
            'status' => 'active',
        ]);

        $creditRequest = CreditRequest::query()->create([
            'agency_id' => $admin->agency_id,
            'client_id' => $client->id,
            'code' => 'SOL-VOID-001',
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
            'code' => 'CRE-VOID-001',
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

    private function registerPayment(User $admin, Credit $credit, int $installmentsCount = 1): CreditPayment
    {
        $this->openCashSession($admin);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/payments", [
                'installments_count' => $installmentsCount,
                'payment_method' => CreditPayment::METHOD_CASH,
                'payment_date' => '2026-07-01',
            ])
            ->assertRedirect("/credits/{$credit->id}");

        return CreditPayment::query()->where('credit_id', $credit->id)->latest()->firstOrFail();
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


    public function test_admin_can_void_applied_payment_and_revert_installments(): void
    {
        Carbon::setTestNow('2026-07-01 11:00:00');

        $admin = $this->admin();
        $credit = $this->disbursedCredit($admin);
        $payment = $this->registerPayment($admin, $credit, 1);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/payments/{$payment->id}/void", [
                'void_reason' => 'Pago registrado por error',
            ])
            ->assertRedirect("/credits/{$credit->id}");

        $payment->refresh();

        $this->assertSame(CreditPayment::STATUS_VOIDED, $payment->status);
        $this->assertNotNull($payment->voided_at);
        $this->assertSame($admin->id, $payment->voided_by);
        $this->assertSame('Pago registrado por error', $payment->void_reason);

        $firstInstallment = CreditInstallment::query()
            ->where('credit_id', $credit->id)
            ->where('number', 1)
            ->firstOrFail();

        $this->assertSame(CreditInstallment::STATUS_PENDING, $firstInstallment->status);
        $this->assertSame('0.00', $firstInstallment->paid_amount);
        $this->assertNull($firstInstallment->paid_at);
        $this->assertNull($firstInstallment->credit_payment_id);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'credit_payment.voided',
            'module' => 'credit_payments',
            'auditable_type' => CreditPayment::class,
            'auditable_id' => $payment->id,
        ]);
    }

    public function test_voiding_payment_from_closed_credit_reopens_credit(): void
    {
        Carbon::setTestNow('2026-07-29 11:00:00');

        $admin = $this->admin();
        $credit = $this->disbursedCredit($admin);
        $payment = $this->registerPayment($admin, $credit, 4);

        $this->assertSame(Credit::STATUS_CLOSED, $credit->fresh()->status);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/payments/{$payment->id}/void", [
                'void_reason' => 'Cierre registrado por error',
            ])
            ->assertRedirect("/credits/{$credit->id}");

        $this->assertSame(Credit::STATUS_DISBURSED, $credit->fresh()->status);

        $this->assertSame(4, CreditInstallment::query()
            ->where('credit_id', $credit->id)
            ->where('status', CreditInstallment::STATUS_OVERDUE)
            ->count());

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'credit.reopened_after_payment_void',
            'module' => 'credits',
            'auditable_type' => Credit::class,
            'auditable_id' => $credit->id,
        ]);
    }

    public function test_void_reason_is_required(): void
    {
        Carbon::setTestNow('2026-07-01 11:00:00');

        $admin = $this->admin();
        $credit = $this->disbursedCredit($admin);
        $payment = $this->registerPayment($admin, $credit, 1);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/payments/{$payment->id}/void", [
                'void_reason' => '',
            ])
            ->assertSessionHasErrors('void_reason');

        $this->assertSame(CreditPayment::STATUS_APPLIED, $payment->fresh()->status);
    }

    public function test_payment_cannot_be_voided_twice(): void
    {
        Carbon::setTestNow('2026-07-01 11:00:00');

        $admin = $this->admin();
        $credit = $this->disbursedCredit($admin);
        $payment = $this->registerPayment($admin, $credit, 1);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/payments/{$payment->id}/void", [
                'void_reason' => 'Primera anulación',
            ])
            ->assertRedirect("/credits/{$credit->id}");

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/payments/{$payment->id}/void", [
                'void_reason' => 'Segunda anulación',
            ])
            ->assertRedirect("/credits/{$credit->id}");

        $this->assertSame(1, CreditPayment::query()->where('status', CreditPayment::STATUS_VOIDED)->count());
    }

    public function test_guest_cannot_void_payment(): void
    {
        Carbon::setTestNow('2026-07-01 11:00:00');

        $admin = $this->admin();
        $credit = $this->disbursedCredit($admin);
        $payment = $this->registerPayment($admin, $credit, 1);

        auth()->logout();

        $this->post("/credits/{$credit->id}/payments/{$payment->id}/void", [
            'void_reason' => 'Intento sin sesión',
        ])->assertRedirect('/login');
    }
}
