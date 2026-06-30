<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Services\CreditDelinquencyPolicyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CreditDelinquencyPolicyController extends Controller
{
    public function store(
        Request $request,
        Credit $credit,
        CreditDelinquencyPolicyService $service,
    ): RedirectResponse {
        abort_unless($request->user()?->can('credit_installments.mark_overdue'), 403);

        $result = $service->process(
            today(),
            $credit->id,
            false,
            $request->user()?->id,
        );

        if (($result['blocked'] ?? 0) > 0) {
            return redirect()
                ->route('credits.show', $credit)
                ->with('status', 'Atraso procesado. Cliente bloqueado para nuevos créditos sin aplicar recargos.');
        }

        if (($result['already_blocked'] ?? 0) > 0) {
            return redirect()
                ->route('credits.show', $credit)
                ->with('status', 'Atraso procesado. El cliente ya estaba bloqueado para nuevos créditos.');
        }

        return redirect()
            ->route('credits.show', $credit)
            ->with('status', 'Atraso procesado. No se detectaron clientes nuevos para bloqueo.');
    }
}
