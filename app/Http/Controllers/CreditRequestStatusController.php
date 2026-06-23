<?php

namespace App\Http\Controllers;

use App\Models\CreditRequest;
use App\Services\CreditCreationService;
use App\Support\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CreditRequestStatusController extends Controller
{
    public function submit(Request $request, CreditRequest $creditRequest, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()?->can('credit_requests.update'), 403);

        return $this->transition(
            request: $request,
            creditRequest: $creditRequest,
            auditLogger: $auditLogger,
            allowedCurrentStatuses: [CreditRequest::STATUS_DRAFT],
            newStatus: CreditRequest::STATUS_SUBMITTED,
            event: 'credit_request.submitted',
            successMessage: 'Solicitud enviada correctamente.',
            timestampColumn: 'submitted_at',
        );
    }

    public function startReview(Request $request, CreditRequest $creditRequest, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()?->can('credit_requests.review'), 403);

        return $this->transition(
            request: $request,
            creditRequest: $creditRequest,
            auditLogger: $auditLogger,
            allowedCurrentStatuses: [CreditRequest::STATUS_SUBMITTED],
            newStatus: CreditRequest::STATUS_IN_REVIEW,
            event: 'credit_request.review_started',
            successMessage: 'Solicitud puesta en revisión correctamente.',
            timestampColumn: 'reviewed_at',
            actorColumn: 'reviewed_by',
            noteColumn: 'review_notes',
        );
    }

    public function approve(
        Request $request,
        CreditRequest $creditRequest,
        AuditLogger $auditLogger,
        CreditCreationService $creditCreationService,
    ): RedirectResponse {
        abort_unless($request->user()?->can('credit_requests.approve'), 403);

        return $this->transition(
            request: $request,
            creditRequest: $creditRequest,
            auditLogger: $auditLogger,
            allowedCurrentStatuses: [CreditRequest::STATUS_IN_REVIEW],
            newStatus: CreditRequest::STATUS_APPROVED,
            event: 'credit_request.approved',
            successMessage: 'Solicitud aprobada y crédito generado automáticamente. Pendiente de entrega del dinero.',
            timestampColumn: 'approved_at',
            actorColumn: 'approved_by',
            noteColumn: 'decision_notes',
            afterTransition: function (CreditRequest $approvedRequest) use ($request, $auditLogger, $creditCreationService): void {
                $creditCreationService->createFromCreditRequest(
                    creditRequest: $approvedRequest,
                    user: $request->user(),
                    auditLogger: $auditLogger,
                );
            },
        );
    }

    public function reject(Request $request, CreditRequest $creditRequest, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()?->can('credit_requests.reject'), 403);

        return $this->transition(
            request: $request,
            creditRequest: $creditRequest,
            auditLogger: $auditLogger,
            allowedCurrentStatuses: [CreditRequest::STATUS_IN_REVIEW],
            newStatus: CreditRequest::STATUS_REJECTED,
            event: 'credit_request.rejected',
            successMessage: 'Solicitud rechazada correctamente.',
            timestampColumn: 'rejected_at',
            actorColumn: 'rejected_by',
            noteColumn: 'decision_notes',
        );
    }

    public function cancel(Request $request, CreditRequest $creditRequest, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()?->can('credit_requests.update'), 403);

        return $this->transition(
            request: $request,
            creditRequest: $creditRequest,
            auditLogger: $auditLogger,
            allowedCurrentStatuses: [
                CreditRequest::STATUS_DRAFT,
                CreditRequest::STATUS_SUBMITTED,
                CreditRequest::STATUS_IN_REVIEW,
            ],
            newStatus: CreditRequest::STATUS_CANCELLED,
            event: 'credit_request.cancelled',
            successMessage: 'Solicitud cancelada correctamente.',
            timestampColumn: 'cancelled_at',
            actorColumn: 'cancelled_by',
            noteColumn: 'decision_notes',
        );
    }

    private function transition(
        Request $request,
        CreditRequest $creditRequest,
        AuditLogger $auditLogger,
        array $allowedCurrentStatuses,
        string $newStatus,
        string $event,
        string $successMessage,
        ?string $timestampColumn = null,
        ?string $actorColumn = null,
        ?string $noteColumn = null,
        ?callable $afterTransition = null,
    ): RedirectResponse {
        $data = $request->validate([
            'status_notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'status_notes.max' => 'La nota interna no puede superar 2000 caracteres.',
        ]);

        if (! in_array($creditRequest->status, $allowedCurrentStatuses, true)) {
            return redirect()
                ->route('credit-requests.show', $creditRequest)
                ->with('error', 'La solicitud no permite esa acción desde su estado actual.');
        }

        $oldStatus = $creditRequest->status;

        $fresh = DB::transaction(function () use (
            $request,
            $creditRequest,
            $auditLogger,
            $newStatus,
            $event,
            $timestampColumn,
            $actorColumn,
            $noteColumn,
            $afterTransition,
            $data,
            $oldStatus,
        ): CreditRequest {
            $changes = [
                'status' => $newStatus,
                'updated_by' => $request->user()?->id,
            ];

            if ($timestampColumn !== null) {
                $changes[$timestampColumn] = now();
            }

            if ($actorColumn !== null) {
                $changes[$actorColumn] = $request->user()?->id;
            }

            if ($noteColumn !== null && filled($data['status_notes'] ?? null)) {
                $changes[$noteColumn] = $data['status_notes'];
            }

            $creditRequest->fill($changes);
            $creditRequest->save();

            $fresh = $creditRequest->fresh(['client', 'credit']);

            $auditLogger->log(
                event: $event,
                module: 'credit_requests',
                auditable: $fresh,
                oldValues: [
                    'status' => $oldStatus,
                ],
                newValues: [
                    'status' => $fresh->status,
                    'submitted_at' => $fresh->submitted_at,
                    'reviewed_at' => $fresh->reviewed_at,
                    'approved_at' => $fresh->approved_at,
                    'rejected_at' => $fresh->rejected_at,
                    'cancelled_at' => $fresh->cancelled_at,
                ],
                context: [
                    'action' => $event,
                    'credit_request_code' => $fresh->code,
                    'client_id' => $fresh->client_id,
                    'client_code' => $fresh->client?->code,
                    'old_status' => $oldStatus,
                    'new_status' => $fresh->status,
                    'status_note' => $data['status_notes'] ?? null,
                ],
                user: $request->user(),
            );

            if ($afterTransition !== null) {
                $afterTransition($fresh);
            }

            return $fresh->fresh(['client', 'credit']);
        });

        return redirect()
            ->route('credit-requests.show', $fresh)
            ->with('status', $successMessage);
    }
}
