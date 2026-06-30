<?php

namespace App\Http\Controllers;

use App\Services\CreditLateFeeService;
use App\Support\Settings\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CreditLateFeeSettingController extends Controller
{
    public function edit(Request $request, CreditLateFeeService $creditLateFeeService): View
    {
        abort_unless($request->user()?->can('settings.view'), 403);

        return view('settings.credit-late-fees', [
            'configuration' => $creditLateFeeService->configuration(),
            'types' => [
                CreditLateFeeService::TYPE_FIXED => 'Monto fijo por cuota vencida',
                CreditLateFeeService::TYPE_PERCENTAGE => 'Porcentaje sobre capital + interés',
            ],
        ]);
    }

    public function update(
        Request $request,
        SettingsRepository $settingsRepository,
    ): RedirectResponse {
        abort_unless($request->user()?->can('settings.update'), 403);

        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'type' => ['required', Rule::in([
                CreditLateFeeService::TYPE_FIXED,
                CreditLateFeeService::TYPE_PERCENTAGE,
            ])],
            'fixed_amount' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'grace_days' => ['required', 'integer', 'min:0', 'max:30'],
        ], [
            'type.required' => 'Debes seleccionar el tipo de mora.',
            'type.in' => 'El tipo de mora seleccionado no es válido.',
            'fixed_amount.required' => 'El monto fijo es obligatorio.',
            'fixed_amount.numeric' => 'El monto fijo debe ser numérico.',
            'fixed_amount.min' => 'El monto fijo no puede ser negativo.',
            'fixed_amount.max' => 'El monto fijo es demasiado alto.',
            'percentage.required' => 'El porcentaje es obligatorio.',
            'percentage.numeric' => 'El porcentaje debe ser numérico.',
            'percentage.min' => 'El porcentaje no puede ser negativo.',
            'percentage.max' => 'El porcentaje no puede superar 100%.',
            'grace_days.required' => 'Los días de gracia son obligatorios.',
            'grace_days.integer' => 'Los días de gracia deben ser un número entero.',
            'grace_days.min' => 'Los días de gracia no pueden ser negativos.',
            'grace_days.max' => 'Los días de gracia no pueden superar 30 días.',
        ]);

        $enabled = $request->boolean('enabled');
        $type = (string) $data['type'];
        $fixedAmount = round((float) $data['fixed_amount'], 2);
        $percentage = round((float) $data['percentage'], 4);
        $graceDays = (int) $data['grace_days'];

        if ($enabled && $type === CreditLateFeeService::TYPE_FIXED && $fixedAmount <= 0) {
            return back()
                ->withErrors(['fixed_amount' => 'Para activar mora fija, el monto fijo debe ser mayor a Q0.00.'])
                ->withInput();
        }

        if ($enabled && $type === CreditLateFeeService::TYPE_PERCENTAGE && $percentage <= 0) {
            return back()
                ->withErrors(['percentage' => 'Para activar mora porcentual, el porcentaje debe ser mayor a 0%.'])
                ->withInput();
        }

        $user = $request->user();

        $settingsRepository->set(
            key: CreditLateFeeService::SETTING_ENABLED,
            value: $enabled,
            group: 'credits',
            type: 'boolean',
            description: 'Activa o desactiva la aplicación controlada de mora en cuotas vencidas.',
            isPublic: false,
            isLocked: false,
            updatedBy: $user,
        );

        $settingsRepository->set(
            key: CreditLateFeeService::SETTING_TYPE,
            value: $type,
            group: 'credits',
            type: 'string',
            description: 'Tipo de mora: fixed para monto fijo o percentage para porcentaje sobre capital más interés de la cuota.',
            isPublic: false,
            isLocked: false,
            updatedBy: $user,
        );

        $settingsRepository->set(
            key: CreditLateFeeService::SETTING_FIXED_AMOUNT,
            value: number_format($fixedAmount, 2, '.', ''),
            group: 'credits',
            type: 'decimal',
            description: 'Monto fijo de mora por cuota vencida. Se aplica una sola vez por cuota.',
            isPublic: false,
            isLocked: false,
            updatedBy: $user,
        );

        $settingsRepository->set(
            key: CreditLateFeeService::SETTING_PERCENTAGE,
            value: number_format($percentage, 4, '.', ''),
            group: 'credits',
            type: 'decimal',
            description: 'Porcentaje de mora por cuota vencida sobre capital más interés. Se aplica una sola vez por cuota.',
            isPublic: false,
            isLocked: false,
            updatedBy: $user,
        );

        $settingsRepository->set(
            key: CreditLateFeeService::SETTING_GRACE_DAYS,
            value: $graceDays,
            group: 'credits',
            type: 'integer',
            description: 'Días de gracia antes de aplicar mora. Con 0, aplica desde el primer día posterior al vencimiento.',
            isPublic: false,
            isLocked: false,
            updatedBy: $user,
        );

        return redirect()
            ->route('settings.credit-late-fees.edit')
            ->with('status', 'Configuración de mora actualizada correctamente.');
    }
}
