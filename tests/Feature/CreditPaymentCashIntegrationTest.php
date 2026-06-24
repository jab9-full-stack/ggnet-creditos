<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Client;
use App\Models\Credit;
use App\Models\CreditInstallment;
use App\Models\CreditPayment;
use App\Models\CreditRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreditPaymentCashIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect([
            'credits.view',
            'credit_payments.view',
            'credit_payments.create',
            'credit_payments.void',
        ])->map(fn (string $permission) => Permission::query()->firstOrCreate([
            'name' => $permission,
            'guard_name' => 'web',
        ]));

        $role = Role::query()->firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($permissions);

        $user = User::forceCreate([
            'name' => 'Admin Integracion Caja',
            'email' => 'admin-cash-payments@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $user->assignRole($role);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function credit(User $admin): Credit
    {
        $client = Client::query()->create([
            'code' => 'CLI-CASH-001',
            'first_name' => 'Cliente',
            'last_name' => 'Caja',
            'dpi' => '1000000000101',
            'phone' => '55550001',
            'address_line' => 'Ciudad',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $creditRequest = CreditRequest::query()->create([
            'client_id' => $client->id,
            'code' => 'SOL-CASH-001',
            'status' => CreditRequest::STATUS_APPROVED,
            'requested_amount' => '1800.00',
            'requested_term_weeks' => 4,
            'approved_at' => now()->subDay(),
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
            'approved_by' => $admin->id,
        ]);

        $credit = Credit::query()->create([
            'client_id' => $client->id,
            'credit_request_id' => $creditRequest->id,
            'code' => 'CRE-CASH-001',
            'status' => Credit::STATUS_DISBURSED,
            'principal_amount' => '1800.00',
            'interest_rate_percent' => '25.00',
            'interest_amount' => '450.00',
            'total_amount' => '2250.00',
            'term_weeks' => 4,
            'approved_at' => now()->subDay(),
            'disbursed_at' => now()->subDay(),
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
            'disbursed_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        for ($i = 1; $i <= 4; $i++) {
            CreditInstallment::query()->create([
                'client_id' => $client->id,
                'credit_id' => $credit->id,
                'number' => $i,
                'status' => CreditInstallment::STATUS_PENDING,
                'due_date' => now()->addWeeks($i)->toDateString(),
                'principal_amount' => '450.00',
                'interest_amount' => '112.50',
                'total_amount' => '562.50',
                'paid_amount' => '0.00',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);
        }

        return $credit->fresh(['client', 'installments']);
    }

    private function openCashSession(User $admin): CashSession
    {
        return CashSession::query()->create([
            'agency_id' => $admin->agency_id,
            'user_id' => $admin->id,
            'code' => 'CAJ-INTEGRACION-'.uniqid(),
            'status' => CashSession::STATUS_OPEN,
            'opening_balance' => '100.00',
            'opened_at' => now(),
            'opened_by' => $admin->id,
            'expected_cash_amount' => '100.00',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }

    public function test_cash_payment_requires_open_cash_session(): void
    {
        $admin = $this->admin();
        $credit = $this->credit($admin);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/payments", [
                'installments_count' => 1,
                'payment_method' => CreditPayment::METHOD_CASH,
                'payment_date' => now()->toDateString(),
            ])
            ->assertSessionHas('error', 'Debes abrir caja antes de registrar pagos en efectivo.');

        $this->assertSame(0, CreditPayment::query()->count());
        $this->assertSame(0, CashMovement::query()->count());
    }

    public function test_cash_payment_creates_cash_movement_when_cash_session_is_open(): void
    {
        $admin = $this->admin();
        $credit = $this->credit($admin);
        $session = $this->openCashSession($admin);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/payments", [
                'installments_count' => 1,
                'payment_method' => CreditPayment::METHOD_CASH,
                'payment_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('credits.show', $credit));

        $payment = CreditPayment::query()->firstOrFail();
        $movement = CashMovement::query()->firstOrFail();

        $this->assertSame($session->id, $movement->cash_session_id);
        $this->assertSame($payment->id, $movement->credit_payment_id);
        $this->assertSame(CashMovement::TYPE_CREDIT_PAYMENT, $movement->type);
        $this->assertSame(CashMovement::METHOD_CASH, $movement->method);
        $this->assertSame('562.50', $movement->amount);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'cash_movement.created',
            'module' => 'cash_movements',
            'auditable_type' => CashMovement::class,
            'auditable_id' => $movement->id,
        ]);
    }

    public function test_deposit_payment_creates_financial_movement_with_reference_without_cash_session(): void
    {
        $admin = $this->admin();
        $credit = $this->credit($admin);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/payments", [
                'installments_count' => 1,
                'payment_method' => CreditPayment::METHOD_DEPOSIT,
                'reference' => 'DEP-REF-12345',
                'payment_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('credits.show', $credit));

        $payment = CreditPayment::query()->firstOrFail();
        $movement = CashMovement::query()->firstOrFail();

        $this->assertNull($movement->cash_session_id);
        $this->assertSame($payment->id, $movement->credit_payment_id);
        $this->assertSame(CashMovement::METHOD_DEPOSIT, $movement->method);
        $this->assertSame('DEP-REF-12345', $movement->reference);
        $this->assertSame('562.50', $movement->amount);
    }

    public function test_voiding_payment_voids_linked_cash_movement(): void
    {
        $admin = $this->admin();
        $credit = $this->credit($admin);
        $this->openCashSession($admin);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/payments", [
                'installments_count' => 1,
                'payment_method' => CreditPayment::METHOD_CASH,
                'payment_date' => now()->toDateString(),
            ]);

        $payment = CreditPayment::query()->firstOrFail();
        $movement = CashMovement::query()->firstOrFail();

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/payments/{$payment->id}/void", [
                'void_reason' => 'Pago registrado por error',
            ])
            ->assertRedirect(route('credits.show', $credit));

        $payment->refresh();
        $movement->refresh();

        $this->assertSame(CreditPayment::STATUS_VOIDED, $payment->status);
        $this->assertSame(CashMovement::STATUS_VOIDED, $movement->status);
        $this->assertNotNull($movement->voided_at);
        $this->assertStringContainsString('Pago registrado por error', $movement->void_reason);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'cash_movement.voided',
            'module' => 'cash_movements',
            'auditable_type' => CashMovement::class,
            'auditable_id' => $movement->id,
        ]);
    }
}
