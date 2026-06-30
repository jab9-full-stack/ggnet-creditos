<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\CreditInstallment;
use App\Models\CreditPayment;
use App\Models\User;
use App\Services\CreditPaymentReceiptService;
use App\Support\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreditPaymentVoidService
{
    public function voidPayment(
        CreditPayment $payment,
        string $reason,
        User $user,
        AuditLogger $auditLogger,
    ): CreditPayment {
        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException('El motivo de anulación es obligatorio.');
        }

        return DB::transaction(function () use ($payment, $reason, $user, $auditLogger): CreditPayment {
            /** @var CreditPayment $lockedPayment */
            $lockedPayment = CreditPayment::query()
                ->with(['credit', 'installments'])
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPayment->status !== CreditPayment::STATUS_APPLIED) {
                throw new InvalidArgumentException('Solo se pueden anular pagos aplicados.');
            }

            /** @var Credit $credit */
            $credit = Credit::query()
                ->whereKey($lockedPayment->credit_id)
                ->lockForUpdate()
                ->firstOrFail();

            $installments = CreditInstallment::query()
                ->where('credit_payment_id', $lockedPayment->id)
                ->orderBy('number')
                ->lockForUpdate()
                ->get();

            if ($installments->isEmpty()) {
                throw new InvalidArgumentException('Este pago no tiene cuotas asociadas para revertir.');
            }

            $oldPaymentValues = [
                'status' => $lockedPayment->status,
                'voided_at' => $lockedPayment->voided_at,
                'voided_by' => $lockedPayment->voided_by,
                'void_reason' => $lockedPayment->void_reason,
            ];

            $todayDate = today()->toDateString();

            foreach ($installments as $installment) {
                $newStatus = $installment->due_date && $installment->due_date->toDateString() < $todayDate
                    ? CreditInstallment::STATUS_OVERDUE
                    : CreditInstallment::STATUS_PENDING;

                $installment->fill([
                    'credit_payment_id' => null,
                    'status' => $newStatus,
                    'paid_amount' => 0,
                    'paid_at' => null,
                    'overdue_at' => $newStatus === CreditInstallment::STATUS_OVERDUE ? now() : null,
                    'updated_by' => $user->id,
                ]);

                $installment->save();
            }

            $oldCreditStatus = $credit->status;

            if ($credit->status === Credit::STATUS_CLOSED) {
                $credit->fill([
                    'status' => Credit::STATUS_DISBURSED,
                    'updated_by' => $user->id,
                ]);

                $credit->save();
            }

            $lockedPayment->fill([
                'status' => CreditPayment::STATUS_VOIDED,
                'voided_at' => now(),
                'voided_by' => $user->id,
                'void_reason' => $reason,
                'updated_by' => $user->id,
            ]);

            $lockedPayment->save();

            $freshPayment = $lockedPayment->fresh(['credit', 'client', 'installments']);

            app(CashMovementService::class)->voidCreditPaymentMovement(
                payment: $freshPayment,
                reason: $reason,
                user: $user,
                auditLogger: $auditLogger,
            );

            app(CreditPaymentReceiptService::class)->voidForPayment(
                payment: $freshPayment,
                reason: $reason,
                user: $user,
                auditLogger: $auditLogger,
            );

            $auditLogger->log(
                event: 'credit_payment.voided',
                module: 'credit_payments',
                auditable: $freshPayment,
                oldValues: $oldPaymentValues,
                newValues: [
                    'status' => $freshPayment->status,
                    'voided_at' => $freshPayment->voided_at,
                    'voided_by' => $freshPayment->voided_by,
                    'void_reason' => $freshPayment->void_reason,
                ],
                context: [
                    'action' => 'credit_payment.voided',
                    'payment_id' => $freshPayment->id,
                    'payment_code' => $freshPayment->code,
                    'credit_id' => $credit->id,
                    'credit_code' => $credit->code,
                    'client_id' => $freshPayment->client_id,
                    'amount' => (string) $freshPayment->amount,
                    'installments_reverted' => $installments->pluck('number')->values()->all(),
                    'reason' => $reason,
                ],
                user: $user,
            );

            if ($oldCreditStatus === Credit::STATUS_CLOSED && $credit->fresh()->status === Credit::STATUS_DISBURSED) {
                $auditLogger->log(
                    event: 'credit.reopened_after_payment_void',
                    module: 'credits',
                    auditable: $credit->fresh(),
                    oldValues: [
                        'status' => Credit::STATUS_CLOSED,
                    ],
                    newValues: [
                        'status' => Credit::STATUS_DISBURSED,
                    ],
                    context: [
                        'action' => 'credit.reopened_after_payment_void',
                        'credit_id' => $credit->id,
                        'credit_code' => $credit->code,
                        'payment_id' => $freshPayment->id,
                        'payment_code' => $freshPayment->code,
                    ],
                    user: $user,
                );
            }

            return $freshPayment;
        });
    }
}
