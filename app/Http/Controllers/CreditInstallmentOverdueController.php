<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Services\CreditInstallmentOverdueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CreditInstallmentOverdueController extends Controller
{
    public function store(
        Request $request,
        Credit $credit,
        CreditInstallmentOverdueService $creditInstallmentOverdueService,
    ): RedirectResponse {
        abort_unless($request->user()?->can('credit_installments.mark_overdue'), 403);

        $result = $creditInstallmentOverdueService->markOverdueForCredit($credit);

        if ((int) $result['marked'] === 0) {
            return redirect()
                ->route('credits.show', $credit)
                ->with('status', 'No hay cuotas vencidas nuevas para este crédito.');
        }

        return redirect()
            ->route('credits.show', $credit)
            ->with('status', "Se marcaron {$result['marked']} cuota(s) como vencida(s).");
    }
}
