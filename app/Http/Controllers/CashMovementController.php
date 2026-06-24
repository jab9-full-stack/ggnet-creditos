<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Services\CashMovementService;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class CashMovementController extends Controller
{
    public function store(
        Request $request,
        CashSession $cashSession,
        CashMovementService $cashMovementService,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        abort_unless($request->user()?->can('cash_movements.create'), 403);

        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(CashMovement::MANUAL_TYPES))],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'description' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'type.required' => 'El tipo de movimiento es obligatorio.',
            'type.in' => 'El tipo de movimiento no es válido.',
            'amount.required' => 'El monto es obligatorio.',
            'amount.numeric' => 'El monto debe ser numérico.',
            'amount.min' => 'El monto debe ser mayor a cero.',
            'description.required' => 'La descripción es obligatoria.',
            'description.min' => 'La descripción debe tener al menos 5 caracteres.',
        ]);

        try {
            $movement = $cashMovementService->createManualMovement(
                cashSession: $cashSession,
                type: $data['type'],
                amount: (float) $data['amount'],
                description: $data['description'],
                user: $request->user(),
                auditLogger: $auditLogger,
            );
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['cash_movement' => $exception->getMessage()]);
        }

        return redirect()
            ->route('cash.index')
            ->with('status', "Movimiento {$movement->code} registrado correctamente.");
    }

    public function voidMovement(
        Request $request,
        CashSession $cashSession,
        CashMovement $movement,
        CashMovementService $cashMovementService,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        abort_unless($request->user()?->can('cash_movements.void'), 403);
        abort_unless((int) $movement->cash_session_id === (int) $cashSession->id, 404);

        $data = $request->validate([
            'void_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'void_reason.required' => 'El motivo de anulación es obligatorio.',
            'void_reason.min' => 'El motivo de anulación debe tener al menos 5 caracteres.',
        ]);

        try {
            $voidedMovement = $cashMovementService->voidMovement(
                movement: $movement,
                reason: $data['void_reason'],
                user: $request->user(),
                auditLogger: $auditLogger,
            );
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['cash_movement' => $exception->getMessage()]);
        }

        return redirect()
            ->route('cash.index')
            ->with('status', "Movimiento {$voidedMovement->code} anulado correctamente.");
    }
}
