<?php

use App\Services\CreditLateFeeService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Artisan::command('credits:mark-overdue-installments {--credit_id=} {--dry-run}', function (): int {
    $creditId = $this->option('credit_id') ? (int) $this->option('credit_id') : null;
    $dryRun = (bool) $this->option('dry-run');

    $result = app(\App\Services\CreditInstallmentOverdueService::class)
        ->markOverdue(today(), $creditId, $dryRun);

    $this->info('Resultado vencimientos de cuotas:');
    $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return self::SUCCESS;
})->purpose('Marca como vencidas las cuotas pendientes con fecha anterior a hoy.');



Artisan::command('credits:apply-late-fees {--credit_id=} {--dry-run}', function (): int {
    $creditId = $this->option('credit_id') ? (int) $this->option('credit_id') : null;
    $dryRun = (bool) $this->option('dry-run');

    $result = app(CreditLateFeeService::class)
        ->applyLateFees(today(), $creditId, $dryRun, null);

    $this->info('Resultado aplicación de mora:');
    $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return self::SUCCESS;
})->purpose('Aplica recargo de mora a cuotas vencidas según configuración, sin duplicar recargos.');


Artisan::command('credits:process-overdue {--credit_id=} {--dry-run}', function (): int {
    $creditId = $this->option('credit_id') ? (int) $this->option('credit_id') : null;
    $dryRun = (bool) $this->option('dry-run');

    $overdueResult = app(\App\Services\CreditInstallmentOverdueService::class)
        ->markOverdue(today(), $creditId, $dryRun);

    $lateFeeResult = app(CreditLateFeeService::class)
        ->applyLateFees(today(), $creditId, $dryRun, null);

    $this->info('Resultado proceso vencimientos + mora:');
    $this->line(json_encode([
        'overdue' => $overdueResult,
        'late_fees' => $lateFeeResult,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return self::SUCCESS;
})->purpose('Procesa cuotas vencidas y mora en un solo comando controlado.');


Schedule::command('credits:process-overdue')->dailyAt('01:10')->withoutOverlapping();
