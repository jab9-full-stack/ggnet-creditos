<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

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

