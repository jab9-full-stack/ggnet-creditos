<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\CreditPayment;
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

    public function createCreditPaymentMovement(
        CreditPayment $payment,
        User $user,
        AuditLogger $auditLogger,
    ): CashMovement {
        return DB::transaction(function () use ($payment, $user, $auditLogger): CashMovement {
            $payment->loadMissing(['credit', 'client']);

            if ($payment->status !== CreditPayment::STATUS_APPLIED) {
                throw new InvalidArgumentException('Solo se puede generar caja para pagos aplicados.');
            }

            $existingMovement = CashMovement::query()
                ->where('credit_payment_id', $payment->id)
                ->where('status', CashMovement::STATUS_ACTIVE)
                ->first();

            if ($existingMovement) {
                throw new InvalidArgumentException('Este pago ya tiene un movimiento financiero activo.');
            }

            $cashSession = null;

            if ($payment->method === CreditPayment::METHOD_CASH) {
                $activeSession = app(CashSessionService::class)->activeSessionFor($user);

                if (! $activeSession) {
                    throw new InvalidArgumentException('Debes abrir caja antes de registrar pagos en efectivo.');
                }

                /** @var CashSession $cashSession */
                $cashSession = CashSession::query()
                    ->whereKey($activeSession->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($cashSession->status !== CashSession::STATUS_OPEN) {
                    throw new InvalidArgumentException('La caja seleccionada ya no está abierta.');
                }
            }

            if (in_array($payment->method, [CreditPayment::METHOD_DEPOSIT, CreditPayment::METHOD_TRANSFER], true) && blank($payment->reference)) {
                throw new InvalidArgumentException('Depósito y transferencia requieren referencia.');
            }

            $movement = CashMovement::query()->create([
                'cash_session_id' => $cashSession?->id,
                'agency_id' => $payment->agency_id,
                'client_id' => $payment->client_id,
                'credit_id' => $payment->credit_id,
                'credit_payment_id' => $payment->id,
                'code' => $this->generateCode(),
                'status' => CashMovement::STATUS_ACTIVE,
                'type' => CashMovement::TYPE_CREDIT_PAYMENT,
                'method' => $payment->method,
                'amount' => round((float) $payment->amount, 2),
                'reference' => $payment->reference,
                'description' => "Pago de crédito {$payment->code}",
                'movement_at' => $payment->paid_at ?? now(),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $fresh = $movement->fresh(['cashSession', 'client', 'credit', 'creditPayment', 'createdBy']);

            $auditLogger->log(
                event: 'cash_movement.created',
                module: 'cash_movements',
                auditable: $fresh,
                newValues: $fresh->getAttributes(),
                context: [
                    'action' => 'cash_movement.created_from_credit_payment',
                    'cash_movement_id' => $fresh->id,
                    'cash_movement_code' => $fresh->code,
                    'cash_session_id' => $fresh->cash_session_id,
                    'credit_payment_id' => $payment->id,
                    'payment_code' => $payment->code,
                    'credit_id' => $payment->credit_id,
                    'client_id' => $payment->client_id,
                    'type' => $fresh->type,
                    'method' => $fresh->method,
                    'reference' => $fresh->reference,
                    'amount' => (string) $fresh->amount,
                ],
                user: $user,
            );

            return $fresh;
        });
    }

    public function voidCreditPaymentMovement(
        CreditPayment $payment,
        string $reason,
        User $user,
        AuditLogger $auditLogger,
    ): ?CashMovement {
        return DB::transaction(function () use ($payment, $reason, $user, $auditLogger): ?CashMovement {
            /** @var CashMovement|null $movement */
            $movement = CashMovement::query()
                ->with('cashSession')
                ->where('credit_payment_id', $payment->id)
                ->where('status', CashMovement::STATUS_ACTIVE)
                ->lockForUpdate()
                ->first();

            if (! $movement) {
                return null;
            }

            if ($movement->cashSession && $movement->cashSession->status !== CashSession::STATUS_OPEN) {
                throw new InvalidArgumentException('No se puede anular el pago porque su movimiento pertenece a una caja cerrada.');
            }

            return $this->voidMovement(
                movement: $movement,
                reason: "Anulación de pago {$payment->code}: {$reason}",
                user: $user,
                auditLogger: $auditLogger,
            );
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
