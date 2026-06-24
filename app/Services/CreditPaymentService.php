<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\CreditInstallment;
use App\Models\CreditPayment;
use App\Models\User;
use App\Support\Audit\AuditLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreditPaymentService
{
    public function registerFullInstallmentPayment(
        Credit $credit,
        int $installmentsCount,
        string $method,
        ?string $reference,
        Carbon $paidAt,
        ?string $notes,
        User $user,
        AuditLogger $auditLogger,
    ): CreditPayment {
        if ($installmentsCount < 1) {
            throw new InvalidArgumentException('Debes seleccionar al menos una cuota completa.');
        }

        if ($credit->status !== Credit::STATUS_DISBURSED) {
            throw new InvalidArgumentException('Solo se pueden registrar pagos en créditos entregados.');
        }

        if (in_array($method, [CreditPayment::METHOD_DEPOSIT, CreditPayment::METHOD_TRANSFER], true) && blank($reference)) {
            throw new InvalidArgumentException('La referencia es obligatoria para depósito o transferencia.');
        }

        return DB::transaction(function () use (
            $credit,
            $installmentsCount,
            $method,
            $reference,
            $paidAt,
            $notes,
            $user,
            $auditLogger,
        ): CreditPayment {
            $lockedCredit = Credit::query()
                ->whereKey($credit->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedCredit->status !== Credit::STATUS_DISBURSED) {
                throw new InvalidArgumentException('Solo se pueden registrar pagos en créditos entregados.');
            }

            $payableInstallments = $lockedCredit->installments()
                ->whereIn('status', [CreditInstallment::STATUS_OVERDUE, CreditInstallment::STATUS_PENDING])
                ->orderBy('due_date')
                ->orderBy('number')
                ->lockForUpdate()
                ->get();

            if ($payableInstallments->count() < $installmentsCount) {
                throw new InvalidArgumentException('El crédito no tiene suficientes cuotas pendientes o vencidas para ese pago.');
            }

            $selectedInstallments = $payableInstallments->take($installmentsCount)->values();

            $amount = round((float) $selectedInstallments->sum(fn (CreditInstallment $installment): float => (float) $installment->total_amount), 2);

            $payment = CreditPayment::query()->create([
                'agency_id' => $lockedCredit->agency_id,
                'client_id' => $lockedCredit->client_id,
                'credit_id' => $lockedCredit->id,
                'code' => $this->generateUniqueCode(),
                'method' => $method,
                'reference' => filled($reference) ? trim((string) $reference) : null,
                'installments_count' => $installmentsCount,
                'amount' => $amount,
                'paid_at' => $paidAt,
                'notes' => $notes,
                'received_by' => $user->id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            foreach ($selectedInstallments as $installment) {
                $installment->fill([
                    'credit_payment_id' => $payment->id,
                    'status' => CreditInstallment::STATUS_PAID,
                    'paid_amount' => $installment->total_amount,
                    'paid_at' => $paidAt,
                    'updated_by' => $user->id,
                ]);

                $installment->save();
            }

            $remainingPending = $lockedCredit->installments()
                ->whereIn('status', [CreditInstallment::STATUS_PENDING, CreditInstallment::STATUS_OVERDUE])
                ->count();

            $oldCreditStatus = $lockedCredit->status;

            if ($remainingPending === 0) {
                $lockedCredit->fill([
                    'status' => Credit::STATUS_CLOSED,
                    'updated_by' => $user->id,
                ]);

                $lockedCredit->save();
            }

            $payment->load(['credit', 'client', 'installments']);

            $auditLogger->log(
                event: 'credit_payment.created',
                module: 'credit_payments',
                auditable: $payment,
                newValues: $payment->getAttributes(),
                context: [
                    'action' => 'credit_payment.created',
                    'payment_code' => $payment->code,
                    'credit_id' => $lockedCredit->id,
                    'credit_code' => $lockedCredit->code,
                    'client_id' => $lockedCredit->client_id,
                    'installments_count' => $installmentsCount,
                    'installment_numbers' => $selectedInstallments->pluck('number')->values()->all(),
                    'amount' => (string) $payment->amount,
                    'method' => $payment->method,
                    'reference' => $payment->reference,
                ],
                user: $user,
            );

            if ($remainingPending === 0) {
                $auditLogger->log(
                    event: 'credit.closed',
                    module: 'credits',
                    auditable: $lockedCredit->fresh(),
                    oldValues: [
                        'status' => $oldCreditStatus,
                    ],
                    newValues: [
                        'status' => Credit::STATUS_CLOSED,
                    ],
                    context: [
                        'action' => 'credit.closed',
                        'credit_id' => $lockedCredit->id,
                        'credit_code' => $lockedCredit->code,
                        'payment_id' => $payment->id,
                        'payment_code' => $payment->code,
                    ],
                    user: $user,
                );
            }

            return $payment;
        });
    }

    private function generateUniqueCode(): string
    {
        $nextId = (CreditPayment::query()->withTrashed()->max('id') ?? 0) + 1;
        $code = 'PAG-'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);

        while (CreditPayment::query()->withTrashed()->where('code', $code)->exists()) {
            $nextId++;
            $code = 'PAG-'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
        }

        return $code;
    }
}
