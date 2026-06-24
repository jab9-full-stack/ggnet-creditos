<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\CreditInstallment;
use App\Support\Audit\AuditLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreditInstallmentOverdueService
{
    public function markOverdue(Carbon|string|null $today = null, ?int $creditId = null, bool $dryRun = false): array
    {
        $todayDate = $today ? Carbon::parse($today)->toDateString() : today()->toDateString();

        $query = CreditInstallment::query()
            ->with(['credit:id,code,status,total_amount', 'client:id,code,first_name,middle_name,last_name,second_last_name,married_name'])
            ->where('status', CreditInstallment::STATUS_PENDING)
            ->whereDate('due_date', '<', $todayDate)
            ->orderBy('due_date')
            ->orderBy('number');

        if ($creditId) {
            $query->where('credit_id', $creditId);
        }

        /** @var Collection<int, CreditInstallment> $installments */
        $installments = $query->get();

        if ($dryRun) {
            return [
                'today' => $todayDate,
                'dry_run' => true,
                'found' => $installments->count(),
                'marked' => 0,
                'installments' => $installments->map(fn (CreditInstallment $installment): array => [
                    'id' => $installment->id,
                    'credit_id' => $installment->credit_id,
                    'credit_code' => $installment->credit?->code,
                    'number' => $installment->number,
                    'due_date' => $installment->due_date?->toDateString(),
                    'total_amount' => (string) $installment->total_amount,
                    'status' => $installment->status,
                ])->values()->all(),
            ];
        }

        $marked = 0;

        DB::transaction(function () use ($installments, $todayDate, &$marked): void {
            $auditLogger = app(AuditLogger::class);

            foreach ($installments as $installment) {
                /** @var CreditInstallment|null $locked */
                $locked = CreditInstallment::query()
                    ->with(['credit:id,code,status,total_amount', 'client:id,code'])
                    ->whereKey($installment->id)
                    ->lockForUpdate()
                    ->first();

                if (! $locked || $locked->status !== CreditInstallment::STATUS_PENDING) {
                    continue;
                }

                if (! $locked->due_date || $locked->due_date->toDateString() >= $todayDate) {
                    continue;
                }

                $oldValues = [
                    'status' => $locked->status,
                    'overdue_at' => $locked->overdue_at,
                ];

                $locked->fill([
                    'status' => CreditInstallment::STATUS_OVERDUE,
                    'overdue_at' => now(),
                ]);

                $locked->save();

                $marked++;

                $auditLogger->log(
                    event: 'credit_installment.marked_overdue',
                    module: 'credit_installments',
                    auditable: $locked,
                    oldValues: $oldValues,
                    newValues: [
                        'status' => $locked->status,
                        'overdue_at' => $locked->overdue_at,
                    ],
                    context: [
                        'action' => 'credit_installment.marked_overdue',
                        'credit_id' => $locked->credit_id,
                        'credit_code' => $locked->credit?->code,
                        'client_id' => $locked->client_id,
                        'installment_id' => $locked->id,
                        'installment_number' => $locked->number,
                        'due_date' => $locked->due_date?->toDateString(),
                        'total_amount' => (string) $locked->total_amount,
                        'today' => $todayDate,
                    ],
                    user: null,
                );
            }
        });

        return [
            'today' => $todayDate,
            'dry_run' => false,
            'found' => $installments->count(),
            'marked' => $marked,
        ];
    }

    public function markOverdueForCredit(Credit $credit, bool $dryRun = false): array
    {
        return $this->markOverdue(today(), $credit->id, $dryRun);
    }
}
