<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\CreditPayment;
use App\Services\CreditPaymentVoidService;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CreditPaymentVoidController extends Controller
{
    public function store(
        Request $request,
        Credit $credit,
        CreditPayment $payment,
        CreditPaymentVoidService $creditPaymentVoidService,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        abort_unless($request->user()?->can('credit_payments.void'), 403);

        abort_unless((int) $payment->credit_id === (int) $credit->id, 404);

        $data = $request->validate([
            'void_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'void_reason.required' => 'El motivo de anulación es obligatorio.',
            'void_reason.min' => 'El motivo de anulación debe tener al menos 5 caracteres.',
            'void_reason.max' => 'El motivo de anulación no puede superar 1000 caracteres.',
        ]);

        try {
            $creditPaymentVoidService->voidPayment(
                payment: $payment,
                reason: $data['void_reason'],
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
            ->with('status', "Pago {$payment->code} anulado correctamente.");
    }
}
