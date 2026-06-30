<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\CreditPaymentReceipt;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CreditPaymentReceiptController extends Controller
{
    public function show(Request $request, Credit $credit, CreditPayment $payment): View
    {
        abort_unless($request->user()?->can('credit_payment_receipts.view'), 403);
        abort_unless((int) $payment->credit_id === (int) $credit->id, 404);

        $receipt = CreditPaymentReceipt::query()
            ->with([
                'agency',
                'client',
                'credit',
                'payment.receivedBy:id,name,email',
                'payment.voidedBy:id,name,email',
                'cashSession',
                'cashMovement',
                'issuedBy:id,name,email',
                'voidedBy:id,name,email',
            ])
            ->where('credit_payment_id', $payment->id)
            ->firstOrFail();

        return view('credit-payment-receipts.show', [
            'credit' => $credit,
            'payment' => $payment,
            'receipt' => $receipt,
        ]);
    }
}
