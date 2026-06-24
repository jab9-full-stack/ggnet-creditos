<?php

namespace App\Services;

use App\Models\CashSession;
use App\Models\User;
use App\Support\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CashSessionService
{
    public function open(
        User $user,
        float $openingBalance,
        ?string $openingNotes,
        AuditLogger $auditLogger,
    ): CashSession {
        if ($openingBalance < 0) {
            throw new InvalidArgumentException('El saldo inicial no puede ser negativo.');
        }

        return DB::transaction(function () use ($user, $openingBalance, $openingNotes, $auditLogger): CashSession {
            $agencyId = $user->agency_id ? (int) $user->agency_id : null;

            $alreadyOpen = CashSession::query()
                ->where('user_id', $user->id)
                ->where('status', CashSession::STATUS_OPEN)
                ->when(
                    $agencyId,
                    fn (Builder $query): Builder => $query->where('agency_id', $agencyId),
                    fn (Builder $query): Builder => $query->whereNull('agency_id')
                )
                ->lockForUpdate()
                ->exists();

            if ($alreadyOpen) {
                throw new InvalidArgumentException('Este usuario ya tiene una caja abierta para esta agencia.');
            }

            $cashSession = CashSession::query()->create([
                'agency_id' => $agencyId,
                'user_id' => $user->id,
                'code' => $this->generateCode(),
                'status' => CashSession::STATUS_OPEN,
                'opening_balance' => round($openingBalance, 2),
                'opened_at' => now(),
                'opened_by' => $user->id,
                'expected_cash_amount' => round($openingBalance, 2),
                'opening_notes' => $openingNotes,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $auditLogger->log(
                event: 'cash_session.opened',
                module: 'cash_sessions',
                auditable: $cashSession,
                newValues: $cashSession->getAttributes(),
                context: [
                    'action' => 'cash_session.opened',
                    'cash_session_id' => $cashSession->id,
                    'cash_session_code' => $cashSession->code,
                    'opening_balance' => (string) $cashSession->opening_balance,
                    'agency_id' => $cashSession->agency_id,
                    'user_id' => $cashSession->user_id,
                ],
                user: $user,
            );

            return $cashSession;
        });
    }

    public function close(
        CashSession $cashSession,
        float $countedCashAmount,
        ?string $closingNotes,
        User $user,
        AuditLogger $auditLogger,
    ): CashSession {
        if ($countedCashAmount < 0) {
            throw new InvalidArgumentException('El saldo contado no puede ser negativo.');
        }

        return DB::transaction(function () use ($cashSession, $countedCashAmount, $closingNotes, $user, $auditLogger): CashSession {
            /** @var CashSession $lockedSession */
            $lockedSession = CashSession::query()
                ->whereKey($cashSession->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSession->status !== CashSession::STATUS_OPEN) {
                throw new InvalidArgumentException('Solo se pueden cerrar cajas abiertas.');
            }

            $expectedCashAmount = $lockedSession->expectedCashAmount();
            $differenceAmount = round($countedCashAmount - $expectedCashAmount, 2);

            $oldValues = [
                'status' => $lockedSession->status,
                'expected_cash_amount' => $lockedSession->expected_cash_amount,
                'counted_cash_amount' => $lockedSession->counted_cash_amount,
                'difference_amount' => $lockedSession->difference_amount,
                'closed_at' => $lockedSession->closed_at,
                'closed_by' => $lockedSession->closed_by,
            ];

            $lockedSession->fill([
                'status' => CashSession::STATUS_CLOSED,
                'expected_cash_amount' => $expectedCashAmount,
                'counted_cash_amount' => round($countedCashAmount, 2),
                'difference_amount' => $differenceAmount,
                'closed_at' => now(),
                'closed_by' => $user->id,
                'closing_notes' => $closingNotes,
                'updated_by' => $user->id,
            ]);

            $lockedSession->save();

            $fresh = $lockedSession->fresh(['agency', 'user', 'openedBy', 'closedBy']);

            $auditLogger->log(
                event: 'cash_session.closed',
                module: 'cash_sessions',
                auditable: $fresh,
                oldValues: $oldValues,
                newValues: [
                    'status' => $fresh->status,
                    'expected_cash_amount' => $fresh->expected_cash_amount,
                    'counted_cash_amount' => $fresh->counted_cash_amount,
                    'difference_amount' => $fresh->difference_amount,
                    'closed_at' => $fresh->closed_at,
                    'closed_by' => $fresh->closed_by,
                ],
                context: [
                    'action' => 'cash_session.closed',
                    'cash_session_id' => $fresh->id,
                    'cash_session_code' => $fresh->code,
                    'expected_cash_amount' => (string) $fresh->expected_cash_amount,
                    'counted_cash_amount' => (string) $fresh->counted_cash_amount,
                    'difference_amount' => (string) $fresh->difference_amount,
                ],
                user: $user,
            );

            return $fresh;
        });
    }

    public function activeSessionFor(User $user): ?CashSession
    {
        $agencyId = $user->agency_id ? (int) $user->agency_id : null;

        return CashSession::query()
            ->with(['agency', 'user', 'openedBy'])
            ->where('user_id', $user->id)
            ->where('status', CashSession::STATUS_OPEN)
            ->when(
                $agencyId,
                fn (Builder $query): Builder => $query->where('agency_id', $agencyId),
                fn (Builder $query): Builder => $query->whereNull('agency_id')
            )
            ->latest('opened_at')
            ->first();
    }

    private function generateCode(): string
    {
        $nextId = (CashSession::query()->withTrashed()->max('id') ?? 0) + 1;
        $code = 'CAJ-'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);

        while (CashSession::query()->withTrashed()->where('code', $code)->exists()) {
            $nextId++;
            $code = 'CAJ-'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
        }

        return $code;
    }
}
