<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Services\CashSessionService;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class CashSessionController extends Controller
{
    public function index(Request $request, CashSessionService $cashSessionService): View
    {
        abort_unless($request->user()?->can('cash.view'), 403);

        $user = $request->user();
        $canSeeReports = $user->can('cash.reports');

        $activeSession = $cashSessionService->activeSessionFor($user);

        $sessions = CashSession::query()
            ->with(['agency', 'user', 'openedBy', 'closedBy'])
            ->when(! $canSeeReports, fn ($query) => $query->where('user_id', $user->id))
            ->latest('opened_at')
            ->limit(20)
            ->get();

        $movements = CashMovement::query()
            ->with(['cashSession', 'createdBy', 'voidedBy', 'creditPayment.receipt:id,credit_payment_id,code,status'])
            ->whereDate('movement_at', now()->toDateString())
            ->when(! $canSeeReports, fn ($query) => $query->where('created_by', $user->id))
            ->latest('movement_at')
            ->limit(50)
            ->get();

        $totalsByMethod = $movements
            ->where('status', CashMovement::STATUS_ACTIVE)
            ->groupBy('method')
            ->map(fn ($items) => round((float) $items->sum(fn (CashMovement $movement): float => $movement->signedAmount()), 2));

        return view('cash.index', [
            'activeSession' => $activeSession,
            'sessions' => $sessions,
            'movements' => $movements,
            'totalsByMethod' => $totalsByMethod,
        ]);
    }

    public function open(
        Request $request,
        CashSessionService $cashSessionService,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        abort_unless($request->user()?->can('cash.open'), 403);

        $data = $request->validate([
            'opening_balance' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'opening_notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'opening_balance.required' => 'El saldo inicial es obligatorio.',
            'opening_balance.numeric' => 'El saldo inicial debe ser numérico.',
            'opening_balance.min' => 'El saldo inicial no puede ser negativo.',
        ]);

        try {
            $cashSession = $cashSessionService->open(
                user: $request->user(),
                openingBalance: (float) $data['opening_balance'],
                openingNotes: $data['opening_notes'] ?? null,
                auditLogger: $auditLogger,
            );
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['cash_session' => $exception->getMessage()]);
        }

        return redirect()
            ->route('cash.index')
            ->with('status', "Caja {$cashSession->code} abierta correctamente.");
    }

    public function close(
        Request $request,
        CashSession $cashSession,
        CashSessionService $cashSessionService,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        abort_unless($request->user()?->can('cash.close'), 403);

        $data = $request->validate([
            'counted_cash_amount' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'closing_notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'counted_cash_amount.required' => 'El saldo contado es obligatorio.',
            'counted_cash_amount.numeric' => 'El saldo contado debe ser numérico.',
            'counted_cash_amount.min' => 'El saldo contado no puede ser negativo.',
        ]);

        try {
            $closedSession = $cashSessionService->close(
                cashSession: $cashSession,
                countedCashAmount: (float) $data['counted_cash_amount'],
                closingNotes: $data['closing_notes'] ?? null,
                user: $request->user(),
                auditLogger: $auditLogger,
            );
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['cash_session' => $exception->getMessage()]);
        }

        return redirect()
            ->route('cash.index')
            ->with('status', "Caja {$closedSession->code} cerrada correctamente.");
    }
}
