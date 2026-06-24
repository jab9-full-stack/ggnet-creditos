<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\User;
use App\Support\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CashMovementService
{
    public function createManualMovement(
        CashSession $cashSession,
        string $type,
        float $amount,
        string $description,
        User $user,
        AuditLogger $auditLogger,
    ): CashMovement {
        if (! array_key_exists($type, CashMovement::MANUAL_TYPES)) {
            throw new InvalidArgumentException('El tipo de movimiento no es válido.');
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('El monto debe ser mayor a cero.');
        }

        return DB::transaction(function () use ($cashSession, $type, $amount, $description, $user, $auditLogger): CashMovement {
            /** @var CashSession $lockedSession */
            $lockedSession = CashSession::query()
                ->whereKey($cashSession->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSession->status !== CashSession::STATUS_OPEN) {
                throw new InvalidArgumentException('Solo se pueden registrar movimientos en una caja abierta.');
            }

            $movement = CashMovement::query()->create([
                'cash_session_id' => $lockedSession->id,
                'agency_id' => $lockedSession->agency_id,
                'code' => $this->generateCode(),
                'status' => CashMovement::STATUS_ACTIVE,
                'type' => $type,
                'method' => CashMovement::METHOD_CASH,
                'amount' => round($amount, 2),
                'reference' => null,
                'description' => $description,
                'movement_at' => now(),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $fresh = $movement->fresh(['cashSession', 'createdBy']);

            $auditLogger->log(
                event: 'cash_movement.created',
                module: 'cash_movements',
                auditable: $fresh,
                newValues: $fresh->getAttributes(),
                context: [
                    'action' => 'cash_movement.created',
                    'cash_movement_id' => $fresh->id,
                    'cash_movement_code' => $fresh->code,
                    'cash_session_id' => $lockedSession->id,
                    'cash_session_code' => $lockedSession->code,
                    'type' => $fresh->type,
                    'method' => $fresh->method,
                    'amount' => (string) $fresh->amount,
                    'signed_amount' => $fresh->signedAmount(),
                ],
                user: $user,
            );

            return $fresh;
        });
    }

    public function voidMovement(
        CashMovement $movement,
        string $reason,
        User $user,
        AuditLogger $auditLogger,
    ): CashMovement {
        return DB::transaction(function () use ($movement, $reason, $user, $auditLogger): CashMovement {
            /** @var CashMovement $lockedMovement */
            $lockedMovement = CashMovement::query()
                ->with('cashSession')
                ->whereKey($movement->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedMovement->status !== CashMovement::STATUS_ACTIVE) {
                throw new InvalidArgumentException('Solo se pueden anular movimientos activos.');
            }

            if ($lockedMovement->cashSession && $lockedMovement->cashSession->status !== CashSession::STATUS_OPEN) {
                throw new InvalidArgumentException('No se pueden anular movimientos de una caja cerrada.');
            }

            $oldValues = [
                'status' => $lockedMovement->status,
                'voided_at' => $lockedMovement->voided_at,
                'voided_by' => $lockedMovement->voided_by,
                'void_reason' => $lockedMovement->void_reason,
            ];

            $lockedMovement->fill([
                'status' => CashMovement::STATUS_VOIDED,
                'voided_at' => now(),
                'voided_by' => $user->id,
                'void_reason' => $reason,
                'updated_by' => $user->id,
            ]);

            $lockedMovement->save();

            $fresh = $lockedMovement->fresh(['cashSession', 'createdBy', 'voidedBy']);

            $auditLogger->log(
                event: 'cash_movement.voided',
                module: 'cash_movements',
                auditable: $fresh,
                oldValues: $oldValues,
                newValues: [
                    'status' => $fresh->status,
                    'voided_at' => $fresh->voided_at,
                    'voided_by' => $fresh->voided_by,
                    'void_reason' => $fresh->void_reason,
                ],
                context: [
                    'action' => 'cash_movement.voided',
                    'cash_movement_id' => $fresh->id,
                    'cash_movement_code' => $fresh->code,
                    'cash_session_id' => $fresh->cash_session_id,
                    'amount' => (string) $fresh->amount,
                    'reason' => $fresh->void_reason,
                ],
                user: $user,
            );

            return $fresh;
        });
    }

    private function generateCode(): string
    {
        $nextId = (CashMovement::query()->withTrashed()->max('id') ?? 0) + 1;
        $code = 'MOV-'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);

        while (CashMovement::query()->withTrashed()->where('code', $code)->exists()) {
            $nextId++;
            $code = 'MOV-'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
        }

        return $code;
    }
}
