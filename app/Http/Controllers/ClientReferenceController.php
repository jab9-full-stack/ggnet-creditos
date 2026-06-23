<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientReference;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClientReferenceController extends Controller
{
    public function index(Request $request, Client $client): View
    {
        abort_unless($request->user()?->can('client_references.view'), 403);

        $type = trim((string) $request->query('type', ''));
        $search = trim((string) $request->query('search', ''));

        $references = $client->references()
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('relationship', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('secondary_phone', 'like', "%{$search}%")
                        ->orWhere('workplace', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('is_primary')
            ->orderBy('full_name')
            ->paginate(15)
            ->withQueryString();

        return view('client-references.index', [
            'client' => $client->load('agency:id,code,name'),
            'references' => $references,
            'type' => $type,
            'search' => $search,
            'types' => $this->types(),
        ]);
    }

    public function create(Request $request, Client $client): View
    {
        abort_unless($request->user()?->can('client_references.create'), 403);

        return view('client-references.create', [
            'client' => $client,
            'reference' => new ClientReference([
                'type' => 'personal',
                'is_primary' => ! $client->references()->exists(),
            ]),
            'types' => $this->types(),
        ]);
    }

    public function store(Request $request, Client $client, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()?->can('client_references.create'), 403);

        $data = $this->validateReference($request);
        $data['created_by'] = $request->user()?->id;
        $data['updated_by'] = $request->user()?->id;

        if ($request->boolean('is_primary')) {
            $client->references()->update(['is_primary' => false]);
            $data['is_primary'] = true;
        } else {
            $data['is_primary'] = false;
        }

        $reference = $client->references()->create($data);

        $auditLogger->created($reference, 'client_references', [
            'action' => 'client_reference.created',
            'client_id' => $client->id,
            'client_code' => $client->code,
        ]);

        return redirect()
            ->route('clients.references.index', $client)
            ->with('status', 'Referencia creada correctamente.');
    }

    public function edit(Request $request, Client $client, ClientReference $reference): View
    {
        abort_unless($request->user()?->can('client_references.update'), 403);
        $this->ensureReferenceBelongsToClient($client, $reference);

        return view('client-references.edit', [
            'client' => $client,
            'reference' => $reference,
            'types' => $this->types(),
        ]);
    }

    public function update(Request $request, Client $client, ClientReference $reference, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()?->can('client_references.update'), 403);
        $this->ensureReferenceBelongsToClient($client, $reference);

        $data = $this->validateReference($request);
        $data['updated_by'] = $request->user()?->id;

        if ($request->boolean('is_primary')) {
            $client->references()
                ->whereKeyNot($reference->id)
                ->update(['is_primary' => false]);

            $data['is_primary'] = true;
        } else {
            $data['is_primary'] = false;
        }

        $oldValues = $reference->only(array_keys($data));

        $reference->fill($data);
        $reference->save();

        $auditLogger->updated(
            model: $reference,
            oldValues: $oldValues,
            newValues: $reference->fresh()->only(array_keys($data)),
            module: 'client_references',
            context: [
                'action' => 'client_reference.updated',
                'client_id' => $client->id,
                'client_code' => $client->code,
            ],
        );

        return redirect()
            ->route('clients.references.index', $client)
            ->with('status', 'Referencia actualizada correctamente.');
    }

    public function destroy(Request $request, Client $client, ClientReference $reference, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()?->can('client_references.delete'), 403);
        $this->ensureReferenceBelongsToClient($client, $reference);

        $auditLogger->deleted($reference, 'client_references', [
            'action' => 'client_reference.deleted',
            'client_id' => $client->id,
            'client_code' => $client->code,
            'reference_name' => $reference->full_name,
        ]);

        $reference->delete();

        return redirect()
            ->route('clients.references.index', $client)
            ->with('status', 'Referencia eliminada correctamente.');
    }

    private function validateReference(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(array_keys($this->types()))],
            'full_name' => ['required', 'string', 'max:180'],
            'relationship' => ['nullable', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9]+$/'],
            'secondary_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9]+$/'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'workplace' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_primary' => ['nullable', 'boolean'],
        ], [
            'type.required' => 'El tipo de referencia es obligatorio.',
            'type.in' => 'El tipo de referencia seleccionado no es válido.',
            'full_name.required' => 'El nombre completo es obligatorio.',
            'phone.required' => 'El teléfono principal es obligatorio.',
            'phone.regex' => 'El teléfono principal solo puede contener números.',
            'secondary_phone.regex' => 'El teléfono secundario solo puede contener números.',
            'notes.max' => 'Las observaciones no pueden superar 2000 caracteres.',
        ]);
    }

    private function types(): array
    {
        return [
            'family' => 'Familiar',
            'personal' => 'Personal',
            'work' => 'Laboral',
            'other' => 'Otro',
        ];
    }

    private function ensureReferenceBelongsToClient(Client $client, ClientReference $reference): void
    {
        abort_unless((int) $reference->client_id === (int) $client->id, 404);
    }
}
