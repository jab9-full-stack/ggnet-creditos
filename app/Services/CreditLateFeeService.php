<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\CreditInstallment;
use App\Models\User;
use App\Support\Audit\AuditLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreditLateFeeService
{
    public const SETTING_ENABLED = 'credit_late_fee_enabled';
    public const SETTING_TYPE = 'credit_late_fee_type';
    public const SETTING_FIXED_AMOUNT = 'credit_late_fee_fixed_amount';
    public const SETTING_PERCENTAGE = 'credit_late_fee_percentage';
    public const SETTING_GRACE_DAYS = 'credit_late_fee_grace_days';

    public const TYPE_FIXED = 'fixed';
    public const TYPE_PERCENTAGE = 'percentage';

    public function configuration(): array
    {
        $type = $this->setting(self::SETTING_TYPE, self::TYPE_FIXED);
        $type = in_array($type, [self::TYPE_FIXED, self::TYPE_PERCENTAGE], true) ? $type : self::TYPE_FIXED;

        return [
            'enabled' => $this->booleanSetting(self::SETTING_ENABLED, false),
            'type' => $type,
            'fixed_amount' => max(0, round((float) $this->setting(self::SETTING_FIXED_AMOUNT, '0.00'), 2)),
            'percentage' => max(0, round((float) $this->setting(self::SETTING_PERCENTAGE, '0.00'), 4)),
            'grace_days' => max(0, (int) $this->setting(self::SETTING_GRACE_DAYS, '0')),
        ];
    }

    public function applyLateFees(
        Carbon|string|null $today = null,
        ?int $creditId = null,
        bool $dryRun = false,
        ?User $user = null,
    ): array {
        $todayDate = $today ? Carbon::parse($today)->startOfDay() : today()->startOfDay();
        $config = $this->configuration();

        if (! $config['enabled']) {
            return [
                'today' => $todayDate->toDateString(),
                'dry_run' => $dryRun,
                'enabled' => false,
                'found' => 0,
                'applied' => 0,
                'skipped' => 0,
                'message' => 'La mora está desactivada en configuración.',
                'configuration' => $config,
            ];
        }

        if ($this->configuredFeeIsZero($config)) {
            return [
                'today' => $todayDate->toDateString(),
                'dry_run' => $dryRun,
                'enabled' => true,
                'found' => 0,
                'applied' => 0,
                'skipped' => 0,
                'message' => 'La mora está activa, pero el monto configurado es 0.',
                'configuration' => $config,
            ];
        }

        $query = CreditInstallment::query()
            ->with(['credit:id,code,status', 'client:id,code,first_name,middle_name,last_name,second_last_name,married_name'])
            ->where('status', CreditInstallment::STATUS_OVERDUE)
            ->where(function ($query): void {
                $query->whereNull('late_fee_applied_at')
                    ->orWhere('late_fee_amount', '<=', 0);
            })
            ->orderBy('due_date')
            ->orderBy('number');

        if ($creditId) {
            $query->where('credit_id', $creditId);
        }

        /** @var Collection<int, CreditInstallment> $installments */
        $installments = $query->get();

        $eligible = $installments
            ->map(function (CreditInstallment $installment) use ($todayDate, $config): ?array {
                $daysOverdue = $this->daysOverdue($installment, $todayDate);

                if ($daysOverdue <= $config['grace_days']) {
                    return null;
                }

                $fee = $this->calculateFee($installment, $config);

                if ($fee <= 0) {
                    return null;
                }

                return [
                    'installment' => $installment,
                    'fee' => $fee,
                    'days_overdue' => $daysOverdue,
                ];
            })
            ->filter()
            ->values();

        if ($dryRun) {
            return [
                'today' => $todayDate->toDateString(),
                'dry_run' => true,
                'enabled' => true,
                'found' => $installments->count(),
                'eligible' => $eligible->count(),
                'applied' => 0,
                'skipped' => $installments->count() - $eligible->count(),
                'configuration' => $config,
                'installments' => $eligible->map(fn (array $item): array => [
                    'id' => $item['installment']->id,
                    'credit_id' => $item['installment']->credit_id,
                    'credit_code' => $item['installment']->credit?->code,
                    'client_id' => $item['installment']->client_id,
                    'client_name' => $item['installment']->client?->fullName(),
                    'number' => $item['installment']->number,
                    'due_date' => $item['installment']->due_date?->toDateString(),
                    'days_overdue' => $item['days_overdue'],
                    'current_total_amount' => (string) $item['installment']->total_amount,
                    'late_fee_amount' => number_format($item['fee'], 2, '.', ''),
                    'new_total_amount' => number_format(round((float) $item['installment']->total_amount + $item['fee'], 2), 2, '.', ''),
                ])->all(),
            ];
        }

        $applied = 0;
        $auditLogger = app(AuditLogger::class);

        DB::transaction(function () use ($eligible, $todayDate, $config, $user, $auditLogger, &$applied): void {
            foreach ($eligible as $item) {
                /** @var CreditInstallment|null $locked */
                $locked = CreditInstallment::query()
                    ->with(['credit:id,code,status', 'client:id,code'])
                    ->whereKey($item['installment']->id)
                    ->lockForUpdate()
                    ->first();

                if (! $locked || $locked->status !== CreditInstallment::STATUS_OVERDUE) {
                    continue;
                }

                if ($locked->late_fee_applied_at || (float) $locked->late_fee_amount > 0) {
                    continue;
                }

                $daysOverdue = $this->daysOverdue($locked, $todayDate);

                if ($daysOverdue <= $config['grace_days']) {
                    continue;
                }

                $fee = $this->calculateFee($locked, $config);

                if ($fee <= 0) {
                    continue;
                }

                $oldValues = [
                    'late_fee_amount' => $locked->late_fee_amount,
                    'late_fee_days' => $locked->late_fee_days,
                    'late_fee_applied_at' => $locked->late_fee_applied_at,
                    'late_fee_applied_by' => $locked->late_fee_applied_by,
                    'total_amount' => $locked->total_amount,
                ];

                $newTotalAmount = round((float) $locked->total_amount + $fee, 2);

                $locked->fill([
                    'late_fee_amount' => $fee,
                    'late_fee_days' => $daysOverdue,
                    'late_fee_applied_at' => now(),
                    'late_fee_applied_by' => $user?->id,
                    'late_fee_notes' => $this->noteFor($config, $fee, $daysOverdue),
                    'total_amount' => $newTotalAmount,
                    'updated_by' => $user?->id,
                ]);

                $locked->save();

                $applied++;

                $auditLogger->log(
                    event: 'credit_installment.late_fee_applied',
                    module: 'credit_installments',
                    auditable: $locked,
                    oldValues: $oldValues,
                    newValues: [
                        'late_fee_amount' => $locked->late_fee_amount,
                        'late_fee_days' => $locked->late_fee_days,
                        'late_fee_applied_at' => $locked->late_fee_applied_at,
                        'late_fee_applied_by' => $locked->late_fee_applied_by,
                        'total_amount' => $locked->total_amount,
                    ],
                    context: [
                        'action' => 'credit_installment.late_fee_applied',
                        'credit_id' => $locked->credit_id,
                        'credit_code' => $locked->credit?->code,
                        'client_id' => $locked->client_id,
                        'installment_id' => $locked->id,
                        'installment_number' => $locked->number,
                        'due_date' => $locked->due_date?->toDateString(),
                        'days_overdue' => $daysOverdue,
                        'grace_days' => $config['grace_days'],
                        'fee_type' => $config['type'],
                        'fee_amount' => number_format($fee, 2, '.', ''),
                        'new_total_amount' => (string) $locked->total_amount,
                    ],
                    user: $user,
                );
            }
        });

        return [
            'today' => $todayDate->toDateString(),
            'dry_run' => false,
            'enabled' => true,
            'found' => $installments->count(),
            'eligible' => $eligible->count(),
            'applied' => $applied,
            'skipped' => $installments->count() - $applied,
            'configuration' => $config,
        ];
    }

    public function applyForCredit(Credit $credit, ?User $user = null, bool $dryRun = false): array
    {
        return $this->applyLateFees(today(), $credit->id, $dryRun, $user);
    }

    private function calculateFee(CreditInstallment $installment, array $config): float
    {
        $baseAmount = round((float) $installment->principal_amount + (float) $installment->interest_amount, 2);

        if ($config['type'] === self::TYPE_PERCENTAGE) {
            return round($baseAmount * ((float) $config['percentage'] / 100), 2);
        }

        return round((float) $config['fixed_amount'], 2);
    }

    private function daysOverdue(CreditInstallment $installment, Carbon $todayDate): int
    {
        if (! $installment->due_date) {
            return 0;
        }

        $dueDate = Carbon::parse($installment->due_date)->startOfDay();

        return max(0, (int) $dueDate->diffInDays($todayDate, false));
    }

    private function configuredFeeIsZero(array $config): bool
    {
        if ($config['type'] === self::TYPE_PERCENTAGE) {
            return (float) $config['percentage'] <= 0;
        }

        return (float) $config['fixed_amount'] <= 0;
    }

    private function noteFor(array $config, float $fee, int $daysOverdue): string
    {
        $mode = $config['type'] === self::TYPE_PERCENTAGE
            ? number_format((float) $config['percentage'], 4, '.', '').'%'
            : 'Q '.number_format((float) $config['fixed_amount'], 2, '.', '');

        return "Mora aplicada: {$mode}; días vencidos: {$daysOverdue}; monto: Q ".number_format($fee, 2, '.', '');
    }

    private function setting(string $key, string $default): string
    {
        $value = DB::table('settings')->where('key', $key)->value('value');

        return $value === null ? $default : (string) $value;
    }

    private function booleanSetting(string $key, bool $default): bool
    {
        $value = strtolower(trim($this->setting($key, $default ? '1' : '0')));

        return in_array($value, ['1', 'true', 'yes', 'on', 'activo', 'activa'], true);
    }
}
