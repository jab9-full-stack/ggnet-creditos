<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\CreditInstallment;
use App\Models\User;
use App\Support\Audit\AuditLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreditDisbursementService
{
    public function disburse(Credit $credit, Carbon $disbursementAt, User $user, AuditLogger $auditLogger): Credit
    {
        if ($credit->status !== Credit::STATUS_APPROVED_PENDING_DISBURSEMENT) {
            throw new InvalidArgumentException('Solo se pueden entregar créditos aprobados pendientes de entrega.');
        }

        if ($credit->installments()->exists()) {
            throw new InvalidArgumentException('Este crédito ya tiene calendario generado.');
        }

        return DB::transaction(function () use ($credit, $disbursementAt, $user, $auditLogger): Credit {
            $oldValues = [
                'status' => $credit->status,
                'disbursed_at' => $credit->disbursed_at,
                'disbursed_by' => $credit->disbursed_by,
            ];

            $credit->fill([
                'status' => Credit::STATUS_DISBURSED,
                'disbursed_at' => $disbursementAt,
                'disbursed_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $credit->save();

            $this->generateInstallments($credit->fresh(), $disbursementAt, $user);

            $fresh = $credit->fresh(['client', 'creditRequest', 'installments']);

            $auditLogger->log(
                event: 'credit.disbursed',
                module: 'credits',
                auditable: $fresh,
                oldValues: $oldValues,
                newValues: [
                    'status' => $fresh->status,
                    'disbursed_at' => $fresh->disbursed_at,
                    'disbursed_by' => $fresh->disbursed_by,
                    'installments_count' => $fresh->installments()->count(),
                ],
                context: [
                    'action' => 'credit.disbursed',
                    'credit_code' => $fresh->code,
                    'credit_request_id' => $fresh->credit_request_id,
                    'credit_request_code' => $fresh->creditRequest?->code,
                    'client_id' => $fresh->client_id,
                    'client_code' => $fresh->client?->code,
                    'principal_amount' => (string) $fresh->principal_amount,
                    'interest_amount' => (string) $fresh->interest_amount,
                    'total_amount' => (string) $fresh->total_amount,
                    'term_weeks' => $fresh->term_weeks,
                    'disbursement_date' => $disbursementAt->toDateString(),
                ],
                user: $user,
            );

            return $fresh;
        });
    }

    private function generateInstallments(Credit $credit, Carbon $disbursementAt, User $user): void
    {
        $termWeeks = 4;

        $principalParts = $this->splitAmount((float) $credit->principal_amount, $termWeeks);
        $interestParts = $this->splitAmount((float) $credit->interest_amount, $termWeeks);

        $startDate = $disbursementAt->copy()->startOfDay();

        for ($number = 1; $number <= $termWeeks; $number++) {
            $principal = $principalParts[$number - 1];
            $interest = $interestParts[$number - 1];

            CreditInstallment::query()->create([
                'agency_id' => $credit->agency_id,
                'client_id' => $credit->client_id,
                'credit_id' => $credit->id,
                'number' => $number,
                'status' => CreditInstallment::STATUS_PENDING,
                'due_date' => $startDate->copy()->addWeeks($number)->toDateString(),
                'principal_amount' => $principal,
                'interest_amount' => $interest,
                'total_amount' => round($principal + $interest, 2),
                'paid_amount' => 0,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        }
    }

    private function splitAmount(float $amount, int $parts): array
    {
        $cents = (int) round($amount * 100);
        $base = intdiv($cents, $parts);
        $result = array_fill(0, $parts, $base);

        $assigned = $base * $parts;
        $remainder = $cents - $assigned;

        if ($remainder > 0) {
            $result[$parts - 1] += $remainder;
        }

        return array_map(
            fn (int $value): float => round($value / 100, 2),
            $result,
        );
    }
}
