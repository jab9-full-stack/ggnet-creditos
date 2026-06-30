<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Client;
use App\Models\Credit;
use App\Models\CreditInstallment;
use App\Models\CreditRequest;
use App\Models\User;
use App\Services\CreditDelinquencyPolicyService;
use App\Services\CreditLateFeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreditDelinquencyPolicyManagementTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agency = Agency::query()->create([
            'code' => 'CENTRAL',
            'name' => 'Agencia Central',
        ]);
    }

    private function admin(): User
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect([
            'credits.view',
            'credit_installments.mark_overdue',
            'credit_installments.apply_late_fee',
            'credit_requests.create',
            'credit_requests.view',
        ])->map(fn (string $permission) => Permission::query()->firstOrCreate([
            'name' => $permission,
            'guard_name' => 'web',
        ]));

        $role = Role::query()->firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($permissions);

        $user = User::query()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Admin Atraso',
            'email' => 'admin-atraso@example.com',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
        ]);

        $user->assignRole($role);

        return $user;
    }


    private function onlyExistingColumns(string $table, array $data): array
    {
        $columns = array_flip(Schema::getColumnListing($table));

        return array_filter(
            $data,
            fn (string $column): bool => isset($columns[$column]),
            ARRAY_FILTER_USE_KEY,
        );
    }

    private function client(): Client
    {
        $data = [
            'agency_id' => $this->agency->id,
            'code' => 'CLI-ATRASO-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'first_name' => 'Cliente',
            'last_name' => 'Atrasado',
            'dpi' => '1234567890101',
            'phone' => '55555555',
        ];

        if (Schema::hasColumn('clients', 'address_line')) {
            $data['address_line'] = 'Dirección de prueba para atraso';
        }

        if (Schema::hasColumn('clients', 'address_reference')) {
            $data['address_reference'] = 'Referencia de prueba';
        }

        if (Schema::hasColumn('clients', 'department')) {
            $data['department'] = 'Guatemala';
        }

        if (Schema::hasColumn('clients', 'municipality')) {
            $data['municipality'] = 'Guatemala';
        }

        if (Schema::hasColumn('clients', 'nit')) {
            $data['nit'] = 'CF';
        }

        if (Schema::hasColumn('clients', 'status')) {
            $data['status'] = 'active';
        }

        if (Schema::hasColumn('clients', 'is_active')) {
            $data['is_active'] = true;
        }

        return Client::query()->forceCreate($data);
    }


    private function creditWithOverdueInstallment(Client $client): Credit
    {
        $creditRequest = CreditRequest::query()->forceCreate($this->onlyExistingColumns('credit_requests', [
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'code' => 'SOL-ATRASO-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'status' => 'approved',
            'requested_amount' => 1000,
            'requested_term_weeks' => 4,
            'purpose' => 'Crédito de prueba para política de atraso',
            'income_source' => 'Prueba',
            'monthly_income' => 3000,
            'approved_at' => now()->subWeeks(5),
            'reviewed_at' => now()->subWeeks(5),
            'decision_notes' => 'Solicitud de prueba aprobada para generar crédito con atraso.',
        ]));

        $credit = Credit::query()->forceCreate($this->onlyExistingColumns('credits', [
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'credit_request_id' => $creditRequest->id,
            'code' => 'CR-ATRASO-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'status' => Credit::STATUS_DISBURSED,
            'principal_amount' => 1000,
            'interest_rate_percent' => 25,
            'interest_amount' => 250,
            'total_amount' => 1250,
            'term_weeks' => 4,
            'approved_at' => now()->subWeeks(5),
            'disbursed_at' => now()->subWeeks(5),
            'notes' => 'Crédito de prueba para atraso sin recargo.',
        ]));

        CreditInstallment::query()->forceCreate($this->onlyExistingColumns('credit_installments', [
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'credit_id' => $credit->id,
            'number' => 1,
            'status' => CreditInstallment::STATUS_PENDING,
            'due_date' => today()->subDay(),
            'principal_amount' => 250,
            'interest_amount' => 62.50,
            'late_fee_amount' => 0,
            'late_fee_days' => 0,
            'total_amount' => 312.50,
            'paid_amount' => 0,
            'notes' => 'Cuota vencida de prueba sin recargo.',
        ]));

        return $credit;
    }


    public function test_delinquency_process_blocks_client_without_charging_late_fee(): void
    {
        $client = $this->client();
        $credit = $this->creditWithOverdueInstallment($client);

        $result = app(CreditDelinquencyPolicyService::class)
            ->process(today(), $credit->id, false, null);

        $this->assertSame(1, $result['blocked']);

        $client->refresh();
        $this->assertNotNull($client->credit_blocked_at);
        $this->assertSame(CreditDelinquencyPolicyService::SOURCE_OVERDUE_INSTALLMENT, $client->credit_block_source);

        $installment = $credit->installments()->first();
        $this->assertSame('0.00', (string) $installment->late_fee_amount);
        $this->assertSame('312.50', (string) $installment->total_amount);
    }

    public function test_delinquency_process_is_idempotent(): void
    {
        $client = $this->client();
        $credit = $this->creditWithOverdueInstallment($client);

        $first = app(CreditDelinquencyPolicyService::class)
            ->process(today(), $credit->id, false, null);

        $second = app(CreditDelinquencyPolicyService::class)
            ->process(today(), $credit->id, false, null);

        $this->assertSame(1, $first['blocked']);
        $this->assertSame(0, $second['blocked']);
        $this->assertSame(1, $second['already_blocked']);

        $installment = $credit->installments()->first();
        $this->assertSame('0.00', (string) $installment->late_fee_amount);
        $this->assertSame('312.50', (string) $installment->total_amount);
    }

    public function test_manual_route_processes_delinquency(): void
    {
        $admin = $this->admin();
        $client = $this->client();
        $credit = $this->creditWithOverdueInstallment($client);

        $this->actingAs($admin)
            ->post("/credits/{$credit->id}/process-delinquency")
            ->assertRedirect("/credits/{$credit->id}");

        $this->assertNotNull($client->fresh()->credit_blocked_at);
    }

    public function test_scheduler_command_processes_delinquency_without_fee(): void
    {
        $client = $this->client();
        $credit = $this->creditWithOverdueInstallment($client);

        $this->artisan('credits:process-overdue')
            ->assertSuccessful();

        $this->assertNotNull($client->fresh()->credit_blocked_at);

        $installment = $credit->installments()->first();
        $this->assertSame('0.00', (string) $installment->late_fee_amount);
        $this->assertSame('312.50', (string) $installment->total_amount);
    }

    public function test_late_fee_service_never_applies_money(): void
    {
        $client = $this->client();
        $credit = $this->creditWithOverdueInstallment($client);

        $result = app(CreditLateFeeService::class)
            ->applyLateFees(today(), $credit->id, false, null);

        $this->assertFalse($result['enabled']);
        $this->assertSame(0, $result['applied']);

        $installment = $credit->installments()->first();
        $this->assertSame('0.00', (string) $installment->late_fee_amount);
        $this->assertSame('312.50', (string) $installment->total_amount);
    }

    public function test_blocked_client_cannot_receive_new_credit_request(): void
    {
        $client = $this->client();
        $client->forceFill([
            'credit_blocked_at' => now(),
            'credit_block_reason' => 'Atraso registrado.',
            'credit_block_source' => CreditDelinquencyPolicyService::SOURCE_OVERDUE_INSTALLMENT,
        ])->save();

        $this->expectException(ValidationException::class);

        CreditRequest::query()->forceCreate([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'code' => 'SOL-BLOCKED-001',
            'status' => 'draft',
            'requested_amount' => 1000,
            'approved_amount' => null,
            'term_weeks' => 4,
            'purpose' => 'Nuevo crédito',
        ]);
    }

    public function test_credit_detail_displays_blocked_policy_message(): void
    {
        $admin = $this->admin();
        $client = $this->client();
        $credit = $this->creditWithOverdueInstallment($client);

        app(CreditDelinquencyPolicyService::class)
            ->process(today(), $credit->id, false, null);

        $this->actingAs($admin)
            ->get("/credits/{$credit->id}")
            ->assertStatus(200)
            ->assertSee('Cliente bloqueado para nuevo crédito')
            ->assertSee('No se cobran recargos de mora');
    }
}
