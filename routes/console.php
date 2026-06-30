<?php

use App\Services\CreditDelinquencyPolicyService;
use App\Services\CreditInstallmentOverdueService;
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

    $result = app(CreditInstallmentOverdueService::class)
        ->markOverdue(today(), $creditId, $dryRun);

    $this->info('Resultado marcado de vencimientos:');
    $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return self::SUCCESS;
})->purpose('Marca como vencidas las cuotas pendientes con fecha anterior a hoy.');

Artisan::command('credits:apply-late-fees {--credit_id=} {--dry-run}', function (): int {
    $creditId = $this->option('credit_id') ? (int) $this->option('credit_id') : null;
    $dryRun = (bool) $this->option('dry-run');

    $result = app(CreditLateFeeService::class)
        ->applyLateFees(today(), $creditId, $dryRun, null);

    $this->info('Resultado política de mora sin recargo:');
    $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return self::SUCCESS;
})->purpose('Compatibilidad: la política vigente no cobra recargos de mora.');

Artisan::command('credits:process-delinquency {--credit_id=} {--dry-run}', function (): int {
    $creditId = $this->option('credit_id') ? (int) $this->option('credit_id') : null;
    $dryRun = (bool) $this->option('dry-run');

    $result = app(CreditDelinquencyPolicyService::class)
        ->process(today(), $creditId, $dryRun, null);

    $this->info('Resultado proceso de atraso sin recargo:');
    $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return self::SUCCESS;
})->purpose('Procesa vencimientos y bloquea nuevos créditos para clientes atrasados, sin recargos.');

Artisan::command('credits:process-overdue {--credit_id=} {--dry-run}', function (): int {
    $creditId = $this->option('credit_id') ? (int) $this->option('credit_id') : null;
    $dryRun = (bool) $this->option('dry-run');

    $result = app(CreditDelinquencyPolicyService::class)
        ->process(today(), $creditId, $dryRun, null);

    $this->info('Resultado proceso vencimientos + atraso sin recargo:');
    $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return self::SUCCESS;
})->purpose('Procesa cuotas vencidas y bloqueo por atraso en un solo comando controlado.');

Schedule::command('credits:process-overdue')->dailyAt('01:10')->withoutOverlapping();
