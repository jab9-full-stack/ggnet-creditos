<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Credit;
use App\Models\CreditInstallment;
use App\Models\User;
use App\Support\Audit\AuditLogger;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreditDelinquencyPolicyService
{
    public const SOURCE_OVERDUE_INSTALLMENT = 'overdue_installment';

    public function __construct(
        private readonly CreditInstallmentOverdueService $overdueService,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function process(
        ?CarbonInterface $today = null,
        ?int $creditId = null,
        bool $dryRun = false,
        ?int $userId = null,
    ): array {
        $today ??= today();

        $overdue = $this->overdueService->markOverdue($today, $creditId, $dryRun);

        $installments = $this->delinquentInstallments($today, $creditId);
        $clientIds = $installments
            ->pluck('client_id')
            ->filter()
            ->unique()
            ->values();

        $clients = Client::query()
            ->whereIn('id', $clientIds)
            ->get()
            ->keyBy('id');

        $blocked = [];
        $alreadyBlocked = [];
        $candidates = [];

        foreach ($clientIds as $clientId) {
            $client = $clients->get($clientId);

            if (! $client) {
                continue;
            }

            $clientInstallments = $installments->where('client_id', $clientId);
            $oldValues = $client->toArray();

            $reason = $this->blockReason($clientInstallments);

            $payload = [
                'client_id' => $client->id,
                'client' => method_exists($client, 'fullName') ? $client->fullName() : ($client->name ?? 'Cliente '.$client->id),
                'reason' => $reason,
                'overdue_installments' => $clientInstallments->count(),
                'old_credit_blocked_at' => $client->credit_blocked_at,
            ];

            $candidates[] = $payload;

            if ($client->credit_blocked_at) {
                $alreadyBlocked[] = $payload;
                continue;
            }

            if (! $dryRun) {
                $client->forceFill([
                    'credit_blocked_at' => now(),
                    'credit_block_reason' => $reason,
                    'credit_block_source' => self::SOURCE_OVERDUE_INSTALLMENT,
                    'credit_last_delinquency_at' => now(),
                ])->save();

                $this->auditLogger->log(
                    event: 'client.credit_blocked',
                    module: 'credits',
                    auditable: $client,
                    oldValues: $oldValues,
                    newValues: $client->fresh()->toArray(),
                    user: $userId ? User::query()->find($userId) : null,
                );
            }

            $blocked[] = $payload;
        }

        return [
            'today' => $today->toDateString(),
            'dry_run' => $dryRun,
            'policy' => 'La mora no genera recargos. El atraso bloquea nuevos créditos para el cliente.',
            'overdue' => $overdue,
            'found_clients' => count($candidates),
            'blocked' => count($blocked),
            'already_blocked' => count($alreadyBlocked),
            'candidates' => $candidates,
        ];
    }

    public function clientIsBlocked(Client|int|null $client): bool
    {
        if ($client === null) {
            return false;
        }

        if (is_int($client)) {
            $client = Client::query()->find($client);
        }

        return (bool) $client?->credit_blocked_at;
    }

    public function blockMessage(Client|int|null $client): string
    {
        if (is_int($client)) {
            $client = Client::query()->find($client);
        }

        if (! $client?->credit_blocked_at) {
            return '';
        }

        return 'Cliente bloqueado para nuevo crédito por atraso registrado. No se cobran recargos de mora.';
    }

    /**
     * @return Collection<int, CreditInstallment>
     */
    private function delinquentInstallments(CarbonInterface $today, ?int $creditId = null): Collection
    {
        return CreditInstallment::query()
            ->with(['client', 'credit'])
            ->whereIn('status', [
                CreditInstallment::STATUS_OVERDUE,
                CreditInstallment::STATUS_PENDING,
            ])
            ->whereDate('due_date', '<', $today->toDateString())
            ->whereHas('credit', function ($query) {
                $query->where('status', Credit::STATUS_DISBURSED);
            })
            ->when($creditId, fn ($query) => $query->where('credit_id', $creditId))
            ->orderBy('due_date')
            ->get();
    }

    private function blockReason(Collection $installments): string
    {
        $count = $installments->count();
        $oldest = $installments->min(fn ($item) => $item->due_date?->format('d/m/Y') ?: '');

        return trim("Atraso registrado en {$count} cuota(s) vencida(s). Primer vencimiento: {$oldest}.");
    }
}
