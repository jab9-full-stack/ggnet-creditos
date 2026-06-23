<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Client;
use App\Models\CreditRequest;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CreditRequestController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('credit_requests.view'), 403);

        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $agencyId = trim((string) $request->query('agency_id', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        $creditRequests = CreditRequest::query()
            ->with(['client:id,code,first_name,middle_name,last_name,second_last_name,married_name,dpi,phone', 'agency:id,code,name'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery
                        ->where('code', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($clientQuery) use ($search): void {
                            $clientQuery
                                ->where('code', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('middle_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('second_last_name', 'like', "%{$search}%")
                                ->orWhere('married_name', 'like', "%{$search}%")
                                ->orWhere('dpi', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($agencyId !== '', fn ($query) => $query->where('agency_id', $agencyId))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('credit-requests.index', [
            'creditRequests' => $creditRequests,
            'search' => $search,
            'status' => $status,
            'agencyId' => $agencyId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'statuses' => CreditRequest::STATUSES,
            'agencies' => Agency::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()?->can('credit_requests.create'), 403);

        return view('credit-requests.create', [
            'creditRequest' => new CreditRequest([
                'status' => CreditRequest::STATUS_DRAFT,
                'agency_id' => $request->user()?->agency_id,
            ]),
            'clients' => $this->clientsForSelect(),
            'agencies' => $this->agenciesForSelect(),
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()?->can('credit_requests.create'), 403);

        $data = $this->validateCreditRequest($request);

        $client = Client::query()->findOrFail($data['client_id']);

        $data['agency_id'] = $data['agency_id'] ?: $client->agency_id ?: $request->user()?->agency_id;
        $data['code'] = $this->generateUniqueCode();
        $data['status'] = CreditRequest::STATUS_DRAFT;
        $data['created_by'] = $request->user()?->id;
        $data['updated_by'] = $request->user()?->id;

        $creditRequest = CreditRequest::query()->create($data);

        $auditLogger->created($creditRequest, 'credit_requests', [
            'action' => 'credit_request.created',
            'credit_request_code' => $creditRequest->code,
            'client_id' => $creditRequest->client_id,
            'client_code' => $creditRequest->client?->code,
            'status' => $creditRequest->status,
        ]);

        return redirect()
            ->route('credit-requests.show', $creditRequest)
            ->with('status', 'Solicitud de crédito creada correctamente.');
    }

    public function show(Request $request, CreditRequest $creditRequest): View
    {
        abort_unless($request->user()?->can('credit_requests.view'), 403);

        $creditRequest->load([
            'agency:id,code,name',
            'client.agency:id,code,name',
            'createdBy:id,name,email',
            'updatedBy:id,name,email',
        ]);

        $client = $creditRequest->client;

        $references = $client->references()
            ->orderByDesc('is_primary')
            ->orderBy('full_name')
            ->limit(5)
            ->get();

        $documents = $client->documents()
            ->with(['uploadedBy:id,name,email', 'verifiedBy:id,name,email'])
            ->latest()
            ->limit(6)
            ->get();

        $referenceCount = $client->references()->count();
        $documentCount = $client->documents()->count();
        $verifiedDocumentCount = $client->documents()->where('status', 'verified')->count();

        return view('credit-requests.show', [
            'creditRequest' => $creditRequest,
            'client' => $client,
            'references' => $references,
            'documents' => $documents,
            'referenceCount' => $referenceCount,
            'documentCount' => $documentCount,
            'verifiedDocumentCount' => $verifiedDocumentCount,
        ]);
    }

    public function edit(Request $request, CreditRequest $creditRequest): View|RedirectResponse
    {
        abort_unless($request->user()?->can('credit_requests.update'), 403);

        if (! $creditRequest->canBeUpdated()) {
            return redirect()
                ->route('credit-requests.show', $creditRequest)
                ->with('error', 'Solo se pueden editar solicitudes en borrador.');
        }

        return view('credit-requests.edit', [
            'creditRequest' => $creditRequest,
            'clients' => $this->clientsForSelect(),
            'agencies' => $this->agenciesForSelect(),
        ]);
    }

    public function update(Request $request, CreditRequest $creditRequest, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()?->can('credit_requests.update'), 403);

        if (! $creditRequest->canBeUpdated()) {
            return redirect()
                ->route('credit-requests.show', $creditRequest)
                ->with('error', 'Solo se pueden editar solicitudes en borrador.');
        }

        $data = $this->validateCreditRequest($request);

        $client = Client::query()->findOrFail($data['client_id']);

        $data['agency_id'] = $data['agency_id'] ?: $client->agency_id ?: $request->user()?->agency_id;
        $data['updated_by'] = $request->user()?->id;

        $oldValues = $creditRequest->only(array_keys($data));

        $creditRequest->fill($data);
        $creditRequest->save();

        $auditLogger->updated(
            model: $creditRequest,
            oldValues: $oldValues,
            newValues: $creditRequest->fresh()->only(array_keys($data)),
            module: 'credit_requests',
            context: [
                'action' => 'credit_request.updated',
                'credit_request_code' => $creditRequest->code,
                'client_id' => $creditRequest->client_id,
                'client_code' => $creditRequest->client?->code,
                'status' => $creditRequest->status,
            ],
        );

        return redirect()
            ->route('credit-requests.show', $creditRequest)
            ->with('status', 'Solicitud de crédito actualizada correctamente.');
    }

    public function destroy(Request $request, CreditRequest $creditRequest, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()?->can('credit_requests.delete'), 403);

        if (! $creditRequest->canBeDeleted()) {
            return redirect()
                ->route('credit-requests.show', $creditRequest)
                ->with('error', 'Solo se pueden eliminar solicitudes en borrador.');
        }

        $auditLogger->deleted($creditRequest, 'credit_requests', [
            'action' => 'credit_request.deleted',
            'credit_request_code' => $creditRequest->code,
            'client_id' => $creditRequest->client_id,
            'client_code' => $creditRequest->client?->code,
            'status' => $creditRequest->status,
        ]);

        $creditRequest->delete();

        return redirect()
            ->route('credit-requests.index')
            ->with('status', 'Solicitud de crédito eliminada correctamente.');
    }

    private function validateCreditRequest(Request $request): array
    {
        return $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'agency_id' => ['nullable', 'exists:agencies,id'],
            'requested_amount' => ['required', 'numeric', 'min:1', 'max:9999999.99'],
            'requested_term_weeks' => ['nullable', 'integer', 'min:1', 'max:104'],
            'purpose' => ['nullable', 'string', 'max:2000'],
            'income_source' => ['nullable', 'string', 'max:180'],
            'monthly_income' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'client_id.required' => 'Debes seleccionar un cliente.',
            'client_id.exists' => 'El cliente seleccionado no existe.',
            'agency_id.exists' => 'La agencia seleccionada no existe.',
            'requested_amount.required' => 'El monto solicitado es obligatorio.',
            'requested_amount.numeric' => 'El monto solicitado debe ser numérico.',
            'requested_amount.min' => 'El monto solicitado debe ser mayor a cero.',
            'requested_term_weeks.integer' => 'El plazo solicitado debe ser un número entero.',
            'requested_term_weeks.min' => 'El plazo solicitado debe ser al menos de 1 semana.',
            'requested_term_weeks.max' => 'El plazo solicitado no puede superar 104 semanas.',
            'monthly_income.numeric' => 'El ingreso mensual aproximado debe ser numérico.',
            'monthly_income.min' => 'El ingreso mensual aproximado no puede ser negativo.',
            'purpose.max' => 'El propósito no puede superar 2000 caracteres.',
            'income_source.max' => 'La fuente de ingresos no puede superar 180 caracteres.',
            'notes.max' => 'Las observaciones no pueden superar 2000 caracteres.',
        ]);
    }

    private function generateUniqueCode(): string
    {
        $nextId = (CreditRequest::query()->withTrashed()->max('id') ?? 0) + 1;
        $code = 'SOL-'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);

        while (CreditRequest::query()->withTrashed()->where('code', $code)->exists()) {
            $nextId++;
            $code = 'SOL-'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
        }

        return $code;
    }

    private function clientsForSelect()
    {
        return Client::query()
            ->where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'agency_id', 'code', 'first_name', 'middle_name', 'last_name', 'second_last_name', 'married_name', 'dpi']);
    }

    private function agenciesForSelect()
    {
        return Agency::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
    }
}
