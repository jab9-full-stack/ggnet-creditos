<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\CreditPaymentReceipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CreditPaymentReceiptController extends Controller
{
    public function show(Request $request, Credit $credit, CreditPayment $payment): Response
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

        $fileName = $receipt->code.'-'.$payment->code.'.pdf';

        return Pdf::loadView('credit-payment-receipts.pdf', [
            'credit' => $credit,
            'payment' => $payment,
            'receipt' => $receipt,
            'generatedAt' => now(),
        ])
            ->setPaper('letter', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false,
            ])
            ->stream($fileName);
    }
}
