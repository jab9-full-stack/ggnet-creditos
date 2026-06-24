<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\CreditInstallment;
use App\Models\CreditPayment;
use App\Services\CreditPaymentService;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class CreditPaymentController extends Controller
{
    public function store(
        Request $request,
        Credit $credit,
        CreditPaymentService $creditPaymentService,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        abort_unless($request->user()?->can('credit_payments.create'), 403);

        $pendingInstallmentsCount = $credit->installments()
            ->where('status', CreditInstallment::STATUS_PENDING)
            ->count();

        $disbursedDate = $credit->disbursed_at?->toDateString() ?? today()->toDateString();

        $data = $request->validate([
            'installments_count' => ['required', 'integer', 'min:1', 'max:'.$pendingInstallmentsCount],
            'method' => ['required', Rule::in(array_keys(CreditPayment::METHODS))],
            'reference' => ['nullable', 'string', 'max:160', 'required_if:method,'.CreditPayment::METHOD_DEPOSIT, 'required_if:method,'.CreditPayment::METHOD_TRANSFER],
            'payment_date' => ['required', 'date', 'after_or_equal:'.$disbursedDate, 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'installments_count.required' => 'Debes seleccionar cuántas cuotas completas se pagarán.',
            'installments_count.integer' => 'La cantidad de cuotas debe ser un número entero.',
            'installments_count.min' => 'Debes seleccionar al menos una cuota.',
            'installments_count.max' => 'No puedes pagar más cuotas de las pendientes.',
            'method.required' => 'Debes seleccionar el método de pago.',
            'method.in' => 'El método de pago no es válido.',
            'reference.required_if' => 'La referencia es obligatoria para depósito o transferencia.',
            'reference.max' => 'La referencia no puede superar 160 caracteres.',
            'payment_date.required' => 'La fecha de pago es obligatoria.',
            'payment_date.date' => 'La fecha de pago no es válida.',
            'payment_date.after_or_equal' => 'La fecha de pago no puede ser anterior a la entrega del crédito.',
            'payment_date.before_or_equal' => 'La fecha de pago no puede ser futura.',
            'notes.max' => 'La nota no puede superar 1000 caracteres.',
        ]);

        try {
            $paidAt = Carbon::parse($data['payment_date'])->setTimeFromTimeString(now()->format('H:i:s'));

            $payment = $creditPaymentService->registerFullInstallmentPayment(
                credit: $credit,
                installmentsCount: (int) $data['installments_count'],
                method: $data['method'],
                reference: $data['reference'] ?? null,
                paidAt: $paidAt,
                notes: $data['notes'] ?? null,
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
            ->with('status', "Pago {$payment->code} registrado correctamente.");
    }
}
