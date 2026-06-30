<?php

namespace Tests\Feature;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Client;
use App\Models\Credit;
use App\Models\CreditInstallment;
use App\Models\CreditPayment;
use App\Models\CreditPaymentReceipt;
use App\Models\CreditRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreditPaymentReceiptManagementTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPermissions(array $permissions): User
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionModels = collect($permissions)->map(fn (string $permission) => Permission::query()->firstOrCreate([
            'name' => $permission,
            'guard_name' => 'web',
        ]));

        $role = Role::query()->firstOrCreate([
            'name' => 'admin-receipts',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($permissionModels);

        $user = User::forceCreate([
            'name' => 'Admin Recibos',
            'email' => 'admin-receipts-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $user->assignRole($role);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function credit(User $user): Credit
    {
        $client = Client::query()->create([
            'code' => 'CLI-REC-001',
            'first_name' => 'Cliente',
            'last_name' => 'Recibo',
            'dpi' => '1000000000901',
            'phone' => '55550901',
            'address_line' => 'Ciudad',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $creditRequest = CreditRequest::query()->create([
            'client_id' => $client->id,
            'code' => 'SOL-REC-001',
            'status' => CreditRequest::STATUS_APPROVED,
            'requested_amount' => '1800.00',
            'requested_term_weeks' => 4,
            'approved_at' => now()->subDay(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'approved_by' => $user->id,
        ]);

        $credit = Credit::query()->create([
            'client_id' => $client->id,
            'credit_request_id' => $creditRequest->id,
            'code' => 'CRE-REC-001',
            'status' => Credit::STATUS_DISBURSED,
            'principal_amount' => '1800.00',
            'interest_rate_percent' => '25.00',
            'interest_amount' => '450.00',
            'total_amount' => '2250.00',
            'term_weeks' => 4,
            'approved_at' => now()->subDay(),
            'disbursed_at' => now()->subDay(),
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'disbursed_by' => $user->id,
            'updated_by' => $user->id,
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
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        }

        return $credit->fresh(['client', 'installments']);
    }

    private function openCashSession(User $user): CashSession
    {
        return CashSession::query()->create([
            'agency_id' => $user->agency_id,
            'user_id' => $user->id,
            'code' => 'CAJ-REC-'.uniqid(),
            'status' => CashSession::STATUS_OPEN,
            'opening_balance' => '100.00',
            'opened_at' => now(),
            'opened_by' => $user->id,
            'expected_cash_amount' => '100.00',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    public function test_receipt_is_generated_when_payment_is_registered(): void
    {
        $admin = $this->userWithPermissions([
            'credits.view',
            'credit_payments.create',
            'credit_payment_receipts.view',
        ]);

        $credit = $this->credit($admin);
        $this->openCashSession($admin);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/payments", [
                'installments_count' => 1,
                'payment_method' => CreditPayment::METHOD_CASH,
                'payment_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('credits.show', $credit));

        $payment = CreditPayment::query()->firstOrFail();
        $receipt = CreditPaymentReceipt::query()->firstOrFail();

        $this->assertSame($payment->id, $receipt->credit_payment_id);
        $this->assertSame($credit->id, $receipt->credit_id);
        $this->assertSame($credit->client_id, $receipt->client_id);
        $this->assertSame(CreditPaymentReceipt::STATUS_ACTIVE, $receipt->status);
        $this->assertSame(CreditPayment::METHOD_CASH, $receipt->method);
        $this->assertSame('562.50', $receipt->amount);
        $this->assertSame(1, $receipt->installments_count);
        $this->assertNotEmpty($receipt->installments_snapshot);
        $this->assertStringStartsWith('REC-', $receipt->code);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'credit_payment_receipt.created',
            'module' => 'credit_payment_receipts',
            'auditable_type' => CreditPaymentReceipt::class,
            'auditable_id' => $receipt->id,
        ]);
    }

    public function test_deposit_receipt_keeps_reference_without_cash_session(): void
    {
        $admin = $this->userWithPermissions([
            'credits.view',
            'credit_payments.create',
            'credit_payment_receipts.view',
        ]);

        $credit = $this->credit($admin);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/payments", [
                'installments_count' => 1,
                'payment_method' => CreditPayment::METHOD_DEPOSIT,
                'reference' => 'DEP-REC-123',
                'payment_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('credits.show', $credit));

        $receipt = CreditPaymentReceipt::query()->firstOrFail();

        $this->assertSame(CreditPayment::METHOD_DEPOSIT, $receipt->method);
        $this->assertSame('DEP-REC-123', $receipt->reference);
        $this->assertNull($receipt->cash_session_id);
        $this->assertNotNull($receipt->cash_movement_id);
    }

    public function test_cash_receipt_keeps_cash_session_and_movement(): void
    {
        $admin = $this->userWithPermissions([
            'credits.view',
            'credit_payments.create',
            'credit_payment_receipts.view',
        ]);

        $credit = $this->credit($admin);
        $session = $this->openCashSession($admin);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/payments", [
                'installments_count' => 1,
                'payment_method' => CreditPayment::METHOD_CASH,
                'payment_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('credits.show', $credit));

        $movement = CashMovement::query()->firstOrFail();
        $receipt = CreditPaymentReceipt::query()->firstOrFail();

        $this->assertSame($session->id, $receipt->cash_session_id);
        $this->assertSame($movement->id, $receipt->cash_movement_id);
    }

    public function test_voiding_payment_keeps_receipt_and_marks_it_voided(): void
    {
        $admin = $this->userWithPermissions([
            'credits.view',
            'credit_payments.create',
            'credit_payments.void',
            'credit_payment_receipts.view',
        ]);

        $credit = $this->credit($admin);
        $this->openCashSession($admin);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/payments", [
                'installments_count' => 1,
                'payment_method' => CreditPayment::METHOD_CASH,
                'payment_date' => now()->toDateString(),
            ]);

        $payment = CreditPayment::query()->firstOrFail();
        $receipt = CreditPaymentReceipt::query()->firstOrFail();

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/payments/{$payment->id}/void", [
                'void_reason' => 'Pago duplicado por error',
            ])
            ->assertRedirect(route('credits.show', $credit));

        $receipt->refresh();

        $this->assertSame(CreditPaymentReceipt::STATUS_VOIDED, $receipt->status);
        $this->assertNotNull($receipt->voided_at);
        $this->assertSame('Pago duplicado por error', $receipt->void_reason);
        $this->assertSame(1, CreditPaymentReceipt::query()->count());

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'credit_payment_receipt.voided',
            'module' => 'credit_payment_receipts',
            'auditable_type' => CreditPaymentReceipt::class,
            'auditable_id' => $receipt->id,
        ]);
    }

    public function test_user_without_permission_cannot_view_receipt(): void
    {
        $admin = $this->userWithPermissions([
            'credits.view',
            'credit_payments.create',
        ]);

        $credit = $this->credit($admin);
        $this->openCashSession($admin);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/payments", [
                'installments_count' => 1,
                'payment_method' => CreditPayment::METHOD_CASH,
                'payment_date' => now()->toDateString(),
            ]);

        $payment = CreditPayment::query()->firstOrFail();

        $this->actingAs($admin)
            ->get("/credits/{$credit->id}/payments/{$payment->id}/receipt")
            ->assertForbidden();
    }

    public function test_receipt_pdf_streams_correctly(): void
    {
        $admin = $this->userWithPermissions([
            'credits.view',
            'credit_payments.create',
            'credit_payment_receipts.view',
        ]);

        $credit = $this->credit($admin);
        $this->openCashSession($admin);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/payments", [
                'installments_count' => 1,
                'payment_method' => CreditPayment::METHOD_CASH,
                'payment_date' => now()->toDateString(),
            ]);

        $payment = CreditPayment::query()->firstOrFail();
        $receipt = CreditPaymentReceipt::query()->firstOrFail();

        $response = $this->actingAs($admin)
            ->get("/credits/{$credit->id}/payments/{$payment->id}/receipt");

        $response->assertStatus(200);

        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type') ?? '');
        $this->assertStringContainsString($receipt->code.'-'.$payment->code.'.pdf', $response->headers->get('content-disposition') ?? '');
    }
}
