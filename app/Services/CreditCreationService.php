<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\CreditRequest;
use App\Models\User;
use App\Support\Audit\AuditLogger;
use InvalidArgumentException;

class CreditCreationService
{
    public function createFromCreditRequest(CreditRequest $creditRequest, ?User $user, AuditLogger $auditLogger): Credit
    {
        if ($creditRequest->status !== CreditRequest::STATUS_APPROVED) {
            throw new InvalidArgumentException('Solo se puede crear crédito desde una solicitud aprobada.');
        }

        $existingCredit = $creditRequest->credit()->first();

        if ($existingCredit) {
            return $existingCredit;
        }

        $principalAmount = round((float) $creditRequest->requested_amount, 2);
        $interestRatePercent = $principalAmount < 2000 ? 25.00 : 20.00;
        $interestAmount = round($principalAmount * ($interestRatePercent / 100), 2);
        $totalAmount = round($principalAmount + $interestAmount, 2);

        $credit = Credit::query()->create([
            'agency_id' => $creditRequest->agency_id,
            'client_id' => $creditRequest->client_id,
            'credit_request_id' => $creditRequest->id,
            'code' => $this->generateUniqueCode(),
            'status' => Credit::STATUS_APPROVED_PENDING_DISBURSEMENT,
            'principal_amount' => $principalAmount,
            'interest_rate_percent' => $interestRatePercent,
            'interest_amount' => $interestAmount,
            'total_amount' => $totalAmount,
            'term_weeks' => 4,
            'approved_at' => $creditRequest->approved_at ?? now(),
            'created_by' => $user?->id,
            'approved_by' => $creditRequest->approved_by ?: $user?->id,
            'updated_by' => $user?->id,
            'notes' => 'Crédito creado automáticamente desde solicitud aprobada.',
        ]);

        $auditLogger->log(
            event: 'credit.created_from_request',
            module: 'credits',
            auditable: $credit,
            newValues: $credit->getAttributes(),
            context: [
                'action' => 'credit.created_from_request',
                'credit_code' => $credit->code,
                'credit_request_id' => $creditRequest->id,
                'credit_request_code' => $creditRequest->code,
                'client_id' => $credit->client_id,
                'client_code' => $creditRequest->client?->code,
                'principal_amount' => (string) $credit->principal_amount,
                'interest_rate_percent' => (string) $credit->interest_rate_percent,
                'interest_amount' => (string) $credit->interest_amount,
                'total_amount' => (string) $credit->total_amount,
                'term_weeks' => $credit->term_weeks,
            ],
            user: $user,
        );

        return $credit;
    }

    private function generateUniqueCode(): string
    {
        $nextId = (Credit::query()->withTrashed()->max('id') ?? 0) + 1;
        $code = 'CRE-'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);

        while (Credit::query()->withTrashed()->where('code', $code)->exists()) {
            $nextId++;
            $code = 'CRE-'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
        }

        return $code;
    }
}
