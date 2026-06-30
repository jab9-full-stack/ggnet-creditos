<?php

namespace Database\Seeders;

use App\Services\CreditLateFeeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use JsonException;

class CreditLateFeeSettingSeeder extends Seeder
{
    /**
     * @throws JsonException
     */
    public function run(): void
    {
        $now = now();

        $settings = [
            [
                'key' => CreditLateFeeService::SETTING_ENABLED,
                'value' => false,
                'type' => 'boolean',
                'description' => 'Política vigente: la mora no cobra recargos monetarios; el atraso bloquea nuevos créditos.',
            ],
            [
                'key' => CreditLateFeeService::SETTING_TYPE,
                'value' => CreditLateFeeService::TYPE_FIXED,
                'type' => 'string',
                'description' => 'Referencia técnica conservada por compatibilidad. No se usa para cobrar recargos.',
            ],
            [
                'key' => CreditLateFeeService::SETTING_FIXED_AMOUNT,
                'value' => '0.00',
                'type' => 'decimal',
                'description' => 'Siempre Q0.00. La mora no genera cobro adicional.',
            ],
            [
                'key' => CreditLateFeeService::SETTING_PERCENTAGE,
                'value' => '0.00',
                'type' => 'decimal',
                'description' => 'Siempre 0%. La mora no genera cobro adicional.',
            ],
            [
                'key' => CreditLateFeeService::SETTING_GRACE_DAYS,
                'value' => 0,
                'type' => 'integer',
                'description' => 'Días de referencia para atraso. La consecuencia operativa es bloqueo de nuevos créditos.',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'group' => 'credits',
                    'value' => json_encode($setting['value'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
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
