<?php

namespace App\Http\Controllers;

use App\Services\CreditLateFeeService;
use App\Support\Settings\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CreditLateFeeSettingController extends Controller
{
    public function edit(Request $request, CreditLateFeeService $creditLateFeeService): View
    {
        abort_unless($request->user()?->can('settings.view'), 403);

        return view('settings.credit-late-fees', [
            'configuration' => $creditLateFeeService->configuration(),
        ]);
    }

    public function update(
        Request $request,
        SettingsRepository $settingsRepository,
    ): RedirectResponse {
        abort_unless($request->user()?->can('settings.update'), 403);

        $user = $request->user();

        $settingsRepository->set(
            key: CreditLateFeeService::SETTING_ENABLED,
            value: false,
            group: 'credits',
            type: 'boolean',
            description: 'Política vigente: la mora no cobra recargos monetarios; el atraso bloquea nuevos créditos.',
            isPublic: false,
            isLocked: false,
            updatedBy: $user,
        );

        $settingsRepository->set(
            key: CreditLateFeeService::SETTING_TYPE,
            value: CreditLateFeeService::TYPE_FIXED,
            group: 'credits',
            type: 'string',
            description: 'Referencia técnica conservada por compatibilidad. No se usa para cobrar recargos.',
            isPublic: false,
            isLocked: false,
            updatedBy: $user,
        );

        $settingsRepository->set(
            key: CreditLateFeeService::SETTING_FIXED_AMOUNT,
            value: '0.00',
            group: 'credits',
            type: 'decimal',
            description: 'Siempre Q0.00. La mora no genera cobro adicional.',
            isPublic: false,
            isLocked: false,
            updatedBy: $user,
        );

        $settingsRepository->set(
            key: CreditLateFeeService::SETTING_PERCENTAGE,
            value: '0.00',
            group: 'credits',
            type: 'decimal',
            description: 'Siempre 0%. La mora no genera cobro adicional.',
            isPublic: false,
            isLocked: false,
            updatedBy: $user,
        );

        $settingsRepository->set(
            key: CreditLateFeeService::SETTING_GRACE_DAYS,
            value: 0,
            group: 'credits',
            type: 'integer',
            description: 'Días de referencia para atraso. La consecuencia operativa es bloqueo de nuevos créditos.',
            isPublic: false,
            isLocked: false,
            updatedBy: $user,
        );

        return redirect()
            ->route('settings.credit-late-fees.edit')
            ->with('status', 'Política de atraso confirmada: no se cobran recargos y el atraso bloquea nuevos créditos.');
    }
}
