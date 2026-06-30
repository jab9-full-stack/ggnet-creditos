<?php

namespace Database\Seeders;

use App\Services\CreditLateFeeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CreditLateFeeSettingSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $settings = [
            [
                'key' => CreditLateFeeService::SETTING_ENABLED,
                'value' => '0',
                'type' => 'boolean',
                'description' => 'Activa o desactiva la aplicación controlada de mora en cuotas vencidas.',
            ],
            [
                'key' => CreditLateFeeService::SETTING_TYPE,
                'value' => CreditLateFeeService::TYPE_FIXED,
                'type' => 'string',
                'description' => 'Tipo de mora: fixed para monto fijo o percentage para porcentaje sobre capital más interés de la cuota.',
            ],
            [
                'key' => CreditLateFeeService::SETTING_FIXED_AMOUNT,
                'value' => '0.00',
                'type' => 'decimal',
                'description' => 'Monto fijo de mora por cuota vencida. Se aplica una sola vez por cuota.',
            ],
            [
                'key' => CreditLateFeeService::SETTING_PERCENTAGE,
                'value' => '0.00',
                'type' => 'decimal',
                'description' => 'Porcentaje de mora por cuota vencida sobre capital más interés. Se aplica una sola vez por cuota.',
            ],
            [
                'key' => CreditLateFeeService::SETTING_GRACE_DAYS,
                'value' => '0',
                'type' => 'integer',
                'description' => 'Días de gracia antes de aplicar mora. Con 0, aplica desde el primer día posterior al vencimiento.',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'group' => 'credits',
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'description' => $setting['description'],
                    'is_public' => false,
                    'is_locked' => false,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }
}
