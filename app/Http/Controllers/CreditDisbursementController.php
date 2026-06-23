<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Services\CreditDisbursementService;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class CreditDisbursementController extends Controller
{
    public function store(
        Request $request,
        Credit $credit,
        CreditDisbursementService $creditDisbursementService,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        abort_unless($request->user()?->can('credits.disburse'), 403);

        $approvedDate = $credit->approved_at?->toDateString() ?? today()->toDateString();

        $data = $request->validate([
            'disbursement_date' => [
                'required',
                'date',
                'after_or_equal:'.$approvedDate,
                'before_or_equal:today',
            ],
        ], [
            'disbursement_date.required' => 'La fecha de entrega es obligatoria.',
            'disbursement_date.date' => 'La fecha de entrega no es válida.',
            'disbursement_date.after_or_equal' => 'La fecha de entrega no puede ser anterior a la aprobación.',
            'disbursement_date.before_or_equal' => 'La fecha de entrega no puede ser futura.',
        ]);

        try {
            $disbursementAt = Carbon::parse($data['disbursement_date'])->setTimeFromTimeString(now()->format('H:i:s'));

            $creditDisbursementService->disburse(
                credit: $credit,
                disbursementAt: $disbursementAt,
                user: $request->user(),
                auditLogger: $auditLogger,
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('credits.show', $credit)
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('credits.show', $credit)
            ->with('status', 'Crédito entregado correctamente y calendario generado.');
    }
}
