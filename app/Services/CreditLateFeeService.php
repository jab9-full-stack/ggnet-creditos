<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\User;
use Carbon\CarbonInterface;

class CreditLateFeeService
{
    public const TYPE_FIXED = 'fixed';
    public const TYPE_PERCENTAGE = 'percentage';

    public const SETTING_ENABLED = 'credit_late_fee_enabled';
    public const SETTING_TYPE = 'credit_late_fee_type';
    public const SETTING_FIXED_AMOUNT = 'credit_late_fee_fixed_amount';
    public const SETTING_PERCENTAGE = 'credit_late_fee_percentage';
    public const SETTING_GRACE_DAYS = 'credit_late_fee_grace_days';

    public function configuration(): array
    {
        return [
            'enabled' => false,
            'type' => self::TYPE_FIXED,
            'fixed_amount' => 0.0,
            'percentage' => 0.0,
            'grace_days' => 0,
            'policy' => 'La política vigente no cobra recargos de mora. El atraso bloquea nuevos créditos.',
        ];
    }

    public function applyLateFees(
        ?CarbonInterface $today = null,
        ?int $creditId = null,
        bool $dryRun = false,
        ?int $userId = null,
    ): array {
        $today ??= today();

        return [
            'today' => $today->toDateString(),
            'dry_run' => $dryRun,
            'enabled' => false,
            'found' => 0,
            'applied' => 0,
            'skipped' => 0,
            'message' => 'La política vigente no cobra recargos de mora. Use el proceso de atraso para bloquear nuevos créditos.',
            'configuration' => $this->configuration(),
        ];
    }
    public function applyForCredit(Credit $credit, ?User $user = null, bool $dryRun = false): array
    {
        return $this->applyLateFees(today(), $credit->id, $dryRun, $user?->id);
    }

}

