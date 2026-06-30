<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Credit;
use App\Models\CreditInstallment;
use App\Models\CreditPayment;
use App\Models\CreditPaymentReceipt;
use App\Models\CreditRequest;
use App\Models\User;
use App\Services\CreditLateFeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreditLateFeeManagementTest extends TestCase
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
            'credit_installments.apply_late_fee',
            'credit_payment_receipts.view',
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
            'email' => 'admin-late-fee@example.com',
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
            'code' => 'CLI-000091-MORA',
            'first_name' => 'Cliente',
            'last_name' => 'Mora',
            'dpi' => '1234567890191',
            'phone' => '50255550091',
            'address_line' => 'Zona 91',
            'country' => 'Guatemala',
            'status' => 'active',
        ]);
    }

    private function disbursedCredit(User $admin): Credit
    {
        $client = $this->client($admin);

        $creditRequest = CreditRequest::query()->create([
            'agency_id' => $admin->agency_id,
            'client_id' => $client->id,
            'code' => 'SOL-MORA-001',
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
            'code' => 'CRE-MORA-001',
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

        foreach ([
            1 => '2026-06-20',
            2 => '2026-06-27',
            3 => '2026-07-18',
            4 => '2026-07-25',
        ] as $number => $dueDate) {
            CreditInstallment::query()->create([
                'agency_id' => $admin->agency_id,
                'client_id' => $client->id,
                'credit_id' => $credit->id,
                'number' => $number,
                'status' => CreditInstallment::STATUS_PENDING,
                'due_date' => $dueDate,
                'principal_amount' => 450,
                'interest_amount' => 112.50,
                'total_amount' => 562.50,
                'paid_amount' => 0,
                'created_by' => $admin->id,
            ]);
        }

        return $credit->fresh(['client', 'installments']);
    }

    private function configureLateFee(bool $enabled = true, string $type = CreditLateFeeService::TYPE_FIXED, string $fixed = '25.00', string $percentage = '0.00', int $graceDays = 0): void
    {
        $now = now();

        $values = [
            CreditLateFeeService::SETTING_ENABLED => [$enabled ? '1' : '0', 'boolean'],
            CreditLateFeeService::SETTING_TYPE => [$type, 'string'],
            CreditLateFeeService::SETTING_FIXED_AMOUNT => [$fixed, 'decimal'],
            CreditLateFeeService::SETTING_PERCENTAGE => [$percentage, 'decimal'],
            CreditLateFeeService::SETTING_GRACE_DAYS => [(string) $graceDays, 'integer'],
        ];

        foreach ($values as $key => [$value, $typeName]) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                [
                    'group' => 'credits',
                    'value' => $value,
                    'type' => $typeName,
                    'description' => 'Configuración de prueba de mora.',
                    'is_public' => false,
                    'is_locked' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    public function test_fixed_late_fee_is_applied_once_to_overdue_installments(): void
    {
        Carbon::setTestNow('2026-07-10 10:00:00');

        $admin = $this->admin();
        $credit = $this->disbursedCredit($admin);
        $this->configureLateFee(enabled: true, fixed: '25.00');

        $this->artisan('credits:mark-overdue-installments', [
            '--credit_id' => $credit->id,
        ])->assertSuccessful();

        $this->artisan('credits:apply-late-fees', [
            '--credit_id' => $credit->id,
        ])->assertSuccessful();

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

        $this->assertSame('25.00', $firstInstallment->late_fee_amount);
        $this->assertSame('587.50', $firstInstallment->total_amount);
        $this->assertNotNull($firstInstallment->late_fee_applied_at);

        $this->assertSame('25.00', $secondInstallment->late_fee_amount);
        $this->assertSame('587.50', $secondInstallment->total_amount);

        $this->assertSame('0.00', $thirdInstallment->late_fee_amount);
        $this->assertSame('562.50', $thirdInstallment->total_amount);

        $this->artisan('credits:apply-late-fees', [
            '--credit_id' => $credit->id,
        ])->assertSuccessful();

        $this->assertSame('25.00', $firstInstallment->fresh()->late_fee_amount);
        $this->assertSame('587.50', $firstInstallment->fresh()->total_amount);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'credit_installment.late_fee_applied',
            'module' => 'credit_installments',
            'auditable_type' => CreditInstallment::class,
            'auditable_id' => $firstInstallment->id,
        ]);
    }

    public function test_late_fee_disabled_does_not_modify_installments(): void
    {
        Carbon::setTestNow('2026-07-10 10:00:00');

        $admin = $this->admin();
        $credit = $this->disbursedCredit($admin);
        $this->configureLateFee(enabled: false);

        $this->artisan('credits:mark-overdue-installments', [
            '--credit_id' => $credit->id,
        ])->assertSuccessful();

        $this->artisan('credits:apply-late-fees', [
            '--credit_id' => $credit->id,
        ])->assertSuccessful();

        $firstInstallment = CreditInstallment::query()
            ->where('credit_id', $credit->id)
            ->where('number', 1)
            ->firstOrFail();

        $this->assertSame(CreditInstallment::STATUS_OVERDUE, $firstInstallment->status);
        $this->assertSame('0.00', $firstInstallment->late_fee_amount);
        $this->assertSame('562.50', $firstInstallment->total_amount);
    }

    public function test_payment_includes_late_fee_and_receipt_snapshot_keeps_it(): void
    {
        Carbon::setTestNow('2026-07-10 10:00:00');

        $admin = $this->admin();
        $credit = $this->disbursedCredit($admin);
        $this->configureLateFee(enabled: true, fixed: '25.00');

        $this->artisan('credits:mark-overdue-installments', [
            '--credit_id' => $credit->id,
        ])->assertSuccessful();

        $this->artisan('credits:apply-late-fees', [
            '--credit_id' => $credit->id,
        ])->assertSuccessful();

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/payments", [
                'installments_count' => 1,
                'payment_method' => CreditPayment::METHOD_DEPOSIT,
                'reference' => 'BANCO-MORA-001',
                'payment_date' => '2026-07-10',
            ])
            ->assertRedirect("/credits/{$credit->id}");

        $payment = CreditPayment::query()
            ->where('credit_id', $credit->id)
            ->latest()
            ->firstOrFail();

        $receipt = CreditPaymentReceipt::query()
            ->where('credit_payment_id', $payment->id)
            ->firstOrFail();

        $firstInstallment = CreditInstallment::query()
            ->where('credit_id', $credit->id)
            ->where('number', 1)
            ->firstOrFail();

        $this->assertSame('587.50', $payment->amount);
        $this->assertSame('587.50', $firstInstallment->paid_amount);
        $this->assertSame('25.00', $receipt->installments_snapshot[0]['late_fee_amount']);
    }

    public function test_admin_can_apply_late_fee_from_credit_detail_action(): void
    {
        Carbon::setTestNow('2026-07-10 10:00:00');

        $admin = $this->admin();
        $credit = $this->disbursedCredit($admin);
        $this->configureLateFee(enabled: true, fixed: '25.00');

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/apply-late-fees")
            ->assertRedirect("/credits/{$credit->id}");

        $this->assertSame('25.00', CreditInstallment::query()
            ->where('credit_id', $credit->id)
            ->where('number', 1)
            ->firstOrFail()
            ->late_fee_amount);
    }

    public function test_guest_cannot_apply_late_fee(): void
    {
        Carbon::setTestNow('2026-07-10 10:00:00');

        $admin = $this->admin();
        $credit = $this->disbursedCredit($admin);

        auth()->logout();

        $this->post("/credits/{$credit->id}/apply-late-fees")
            ->assertRedirect('/login');
    }
}
