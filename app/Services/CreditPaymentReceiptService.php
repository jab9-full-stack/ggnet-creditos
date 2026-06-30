<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\CreditPayment;
use App\Models\CreditPaymentReceipt;
use App\Models\User;
use App\Support\Audit\AuditLogger;

class CreditPaymentReceiptService
{
    public function createForPayment(
        CreditPayment $payment,
        ?CashMovement $cashMovement,
        User $user,
        AuditLogger $auditLogger,
    ): CreditPaymentReceipt {
        $payment->loadMissing(['credit', 'client', 'agency', 'installments']);

        $existing = CreditPaymentReceipt::query()
            ->where('credit_payment_id', $payment->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $installments = $payment->installments()
            ->orderBy('number')
            ->get();

        $receipt = CreditPaymentReceipt::query()->create([
            'agency_id' => $payment->agency_id,
            'client_id' => $payment->client_id,
            'credit_id' => $payment->credit_id,
            'credit_payment_id' => $payment->id,
            'cash_session_id' => $cashMovement?->cash_session_id,
            'cash_movement_id' => $cashMovement?->id,
            'code' => $this->generateUniqueCode(),
            'status' => CreditPaymentReceipt::STATUS_ACTIVE,
            'method' => $payment->method,
            'reference' => $payment->reference,
            'installments_count' => $payment->installments_count,
            'installments_snapshot' => $installments->map(fn ($installment): array => [
                'id' => $installment->id,
                'number' => $installment->number,
                'due_date' => $installment->due_date?->toDateString(),
                'principal_amount' => (string) $installment->principal_amount,
                'interest_amount' => (string) $installment->interest_amount,
                'total_amount' => (string) $installment->total_amount,
                'paid_amount' => (string) $installment->paid_amount,
                'paid_at' => $installment->paid_at?->toDateTimeString(),
            ])->values()->all(),
            'amount' => round((float) $payment->amount, 2),
            'issued_at' => $payment->paid_at ?? now(),
            'issued_by' => $payment->received_by ?: $user->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $fresh = $receipt->fresh(['payment', 'credit', 'client', 'cashSession', 'cashMovement', 'issuedBy']);

        $auditLogger->log(
            event: 'credit_payment_receipt.created',
            module: 'credit_payment_receipts',
            auditable: $fresh,
            newValues: $fresh->getAttributes(),
            context: [
                'action' => 'credit_payment_receipt.created',
                'receipt_id' => $fresh->id,
                'receipt_code' => $fresh->code,
                'payment_id' => $payment->id,
                'payment_code' => $payment->code,
                'credit_id' => $payment->credit_id,
                'client_id' => $payment->client_id,
                'cash_session_id' => $fresh->cash_session_id,
                'cash_movement_id' => $fresh->cash_movement_id,
                'amount' => (string) $fresh->amount,
                'method' => $fresh->method,
                'reference' => $fresh->reference,
            ],
            user: $user,
        );

        return $fresh;
    }

    public function voidForPayment(
        CreditPayment $payment,
        string $reason,
        User $user,
        AuditLogger $auditLogger,
    ): ?CreditPaymentReceipt {
        $receipt = CreditPaymentReceipt::query()
            ->where('credit_payment_id', $payment->id)
            ->lockForUpdate()
            ->first();

        if (! $receipt) {
            return null;
        }

        if ($receipt->status === CreditPaymentReceipt::STATUS_VOIDED) {
            return $receipt;
        }

        $oldValues = [
            'status' => $receipt->status,
            'voided_at' => $receipt->voided_at,
            'voided_by' => $receipt->voided_by,
            'void_reason' => $receipt->void_reason,
        ];

        $receipt->fill([
            'status' => CreditPaymentReceipt::STATUS_VOIDED,
            'voided_at' => $payment->voided_at ?? now(),
            'voided_by' => $user->id,
            'void_reason' => $reason,
            'updated_by' => $user->id,
        ]);

        $receipt->save();

        $fresh = $receipt->fresh(['payment', 'credit', 'client', 'voidedBy']);

        $auditLogger->log(
            event: 'credit_payment_receipt.voided',
            module: 'credit_payment_receipts',
            auditable: $fresh,
            oldValues: $oldValues,
            newValues: [
                'status' => $fresh->status,
                'voided_at' => $fresh->voided_at,
                'voided_by' => $fresh->voided_by,
                'void_reason' => $fresh->void_reason,
            ],
            context: [
                'action' => 'credit_payment_receipt.voided',
                'receipt_id' => $fresh->id,
                'receipt_code' => $fresh->code,
                'payment_id' => $payment->id,
                'payment_code' => $payment->code,
                'credit_id' => $payment->credit_id,
                'client_id' => $payment->client_id,
                'reason' => $reason,
            ],
            user: $user,
        );

        return $fresh;
    }

    private function generateUniqueCode(): string
    {
        $nextId = (CreditPaymentReceipt::query()->withTrashed()->max('id') ?? 0) + 1;
        $code = 'REC-'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);

        while (CreditPaymentReceipt::query()->withTrashed()->where('code', $code)->exists()) {
            $nextId++;
            $code = 'REC-'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
        }

        return $code;
    }
}
