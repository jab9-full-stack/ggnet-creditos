<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Services\CreditInstallmentOverdueService;
use App\Services\CreditLateFeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CreditLateFeeController extends Controller
{
    public function store(
        Request $request,
        Credit $credit,
        CreditInstallmentOverdueService $overdueService,
        CreditLateFeeService $lateFeeService,
    ): RedirectResponse {
        abort_unless($request->user()?->can('credit_installments.apply_late_fee'), 403);

        $overdueResult = $overdueService->markOverdueForCredit($credit);
        $lateFeeResult = $lateFeeService->applyForCredit($credit, $request->user());

        if (! ($lateFeeResult['enabled'] ?? false)) {
            return redirect()
                ->route('credits.show', $credit)
                ->with('status', 'Se actualizaron vencimientos. La mora está desactivada en configuración.');
        }

        if ((int) ($lateFeeResult['applied'] ?? 0) === 0) {
            return redirect()
                ->route('credits.show', $credit)
                ->with('status', "Se actualizaron vencimientos ({$overdueResult['marked']}). No hubo recargos de mora nuevos para aplicar.");
        }

        return redirect()
            ->route('credits.show', $credit)
            ->with('status', "Se aplicó mora a {$lateFeeResult['applied']} cuota(s). Vencimientos actualizados: {$overdueResult['marked']}.");
    }
}
