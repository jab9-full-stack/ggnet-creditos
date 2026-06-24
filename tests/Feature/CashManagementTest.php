<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CashManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect([
            'cash.view',
            'cash.open',
            'cash.close',
            'cash_movements.view',
            'cash_movements.create',
            'cash_movements.void',
            'cash.reports',
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
            'name' => 'Admin Caja',
            'email' => 'admin-cash@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $user->assignRole($role);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    public function test_admin_can_open_cash_session(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/cash/open', [
                'opening_balance' => '150.00',
                'opening_notes' => 'Apertura de prueba',
            ])
            ->assertRedirect(route('cash.index'));

        $session = CashSession::query()->firstOrFail();

        $this->assertSame(CashSession::STATUS_OPEN, $session->status);
        $this->assertSame('150.00', $session->opening_balance);
        $this->assertSame($admin->id, $session->user_id);
        $this->assertSame($admin->id, $session->opened_by);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'cash_session.opened',
            'module' => 'cash_sessions',
            'auditable_type' => CashSession::class,
            'auditable_id' => $session->id,
        ]);
    }

    public function test_user_cannot_open_duplicate_active_cash_session(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/cash/open', [
                'opening_balance' => '100.00',
            ]);

        $this->actingAs($admin)
            ->post('/cash/open', [
                'opening_balance' => '200.00',
            ])
            ->assertSessionHasErrors('cash_session');

        $this->assertSame(1, CashSession::query()->where('status', CashSession::STATUS_OPEN)->count());
    }

    public function test_admin_can_create_manual_cash_movement(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/cash/open', [
                'opening_balance' => '100.00',
            ]);

        $session = CashSession::query()->firstOrFail();

        $this->actingAs($admin)
            ->post("/cash/{$session->id}/movements", [
                'type' => CashMovement::TYPE_MANUAL_EXPENSE,
                'amount' => '25.00',
                'description' => 'Compra de papelería',
            ])
            ->assertRedirect(route('cash.index'));

        $movement = CashMovement::query()->firstOrFail();

        $this->assertSame(CashMovement::STATUS_ACTIVE, $movement->status);
        $this->assertSame(CashMovement::TYPE_MANUAL_EXPENSE, $movement->type);
        $this->assertSame(CashMovement::METHOD_CASH, $movement->method);
        $this->assertSame('25.00', $movement->amount);
        $this->assertSame(-25.00, $movement->signedAmount());

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'cash_movement.created',
            'module' => 'cash_movements',
            'auditable_type' => CashMovement::class,
            'auditable_id' => $movement->id,
        ]);
    }

    public function test_cash_close_calculates_expected_counted_and_difference(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/cash/open', [
                'opening_balance' => '100.00',
            ]);

        $session = CashSession::query()->firstOrFail();

        $this->actingAs($admin)
            ->post("/cash/{$session->id}/movements", [
                'type' => CashMovement::TYPE_ADJUSTMENT_IN,
                'amount' => '50.00',
                'description' => 'Ajuste positivo autorizado',
            ]);

        $this->actingAs($admin)
            ->post("/cash/{$session->id}/movements", [
                'type' => CashMovement::TYPE_MANUAL_EXPENSE,
                'amount' => '20.00',
                'description' => 'Egreso autorizado',
            ]);

        $this->actingAs($admin)
            ->post("/cash/{$session->id}/close", [
                'counted_cash_amount' => '120.00',
                'closing_notes' => 'Cierre de prueba',
            ])
            ->assertRedirect(route('cash.index'));

        $session->refresh();

        $this->assertSame(CashSession::STATUS_CLOSED, $session->status);
        $this->assertSame('130.00', $session->expected_cash_amount);
        $this->assertSame('120.00', $session->counted_cash_amount);
        $this->assertSame('-10.00', $session->difference_amount);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'cash_session.closed',
            'module' => 'cash_sessions',
            'auditable_type' => CashSession::class,
            'auditable_id' => $session->id,
        ]);
    }

    public function test_admin_can_void_active_cash_movement(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/cash/open', [
                'opening_balance' => '100.00',
            ]);

        $session = CashSession::query()->firstOrFail();

        $this->actingAs($admin)
            ->post("/cash/{$session->id}/movements", [
                'type' => CashMovement::TYPE_ADJUSTMENT_IN,
                'amount' => '25.00',
                'description' => 'Ajuste positivo autorizado',
            ]);

        $movement = CashMovement::query()->firstOrFail();

        $this->actingAs($admin)
            ->post("/cash/{$session->id}/movements/{$movement->id}/void", [
                'void_reason' => 'Movimiento registrado por error',
            ])
            ->assertRedirect(route('cash.index'));

        $movement->refresh();

        $this->assertSame(CashMovement::STATUS_VOIDED, $movement->status);
        $this->assertNotNull($movement->voided_at);
        $this->assertSame($admin->id, $movement->voided_by);
        $this->assertSame('Movimiento registrado por error', $movement->void_reason);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'cash_movement.voided',
            'module' => 'cash_movements',
            'auditable_type' => CashMovement::class,
            'auditable_id' => $movement->id,
        ]);
    }

    public function test_guest_cannot_access_cash_module(): void
    {
        $this->get('/cash')
            ->assertRedirect('/login');
    }
}
