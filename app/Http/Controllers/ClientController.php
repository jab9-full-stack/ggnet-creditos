<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Client;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('clients.view'), 403);

        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $agencyId = trim((string) $request->query('agency_id', ''));

        $clients = Client::query()
            ->with('agency:id,code,name')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('second_last_name', 'like', "%{$search}%")
                        ->orWhere('married_name', 'like', "%{$search}%")
                        ->orWhere('dpi', 'like', "%{$search}%")
                        ->orWhere('nit', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('secondary_phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($agencyId !== '', fn ($query) => $query->where('agency_id', $agencyId))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        return view('clients.index', [
            'clients' => $clients,
            'search' => $search,
            'status' => $status,
            'agencyId' => $agencyId,
            'agencies' => Agency::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()?->can('clients.create'), 403);

        return view('clients.create', [
            'client' => new Client([
                'country' => 'Guatemala',
                'status' => 'active',
                'agency_id' => $request->user()?->agency_id,
            ]),
            'agencies' => Agency::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()?->can('clients.create'), 403);

        $data = $this->validateClient($request);
        $data['code'] = $this->generateUniqueCode($data['first_name'], $data['last_name']);
        $data['created_by'] = $request->user()?->id;
        $data['updated_by'] = $request->user()?->id;

        $client = Client::query()->create($data);

        $auditLogger->created($client, 'clients', [
            'action' => 'client.created',
            'code' => $client->code,
        ]);

        return redirect()
            ->route('clients.index')
            ->with('status', 'Cliente creado correctamente.');
    }

    public function edit(Request $request, Client $client): View
    {
        abort_unless($request->user()?->can('clients.update'), 403);

        return view('clients.edit', [
            'client' => $client,
            'agencies' => Agency::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
        ]);
    }

    public function update(Request $request, Client $client, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()?->can('clients.update'), 403);

        $data = $this->validateClient($request, $client);
        $data['updated_by'] = $request->user()?->id;

        $oldValues = $client->only(array_keys($data));

        $client->fill($data);
        $client->save();

        $auditLogger->updated(
            model: $client,
            oldValues: $oldValues,
            newValues: $client->fresh()->only(array_keys($data)),
            module: 'clients',
            context: [
                'action' => 'client.updated',
                'code' => $client->code,
            ],
        );

        return redirect()
            ->route('clients.index')
            ->with('status', 'Cliente actualizado correctamente.');
    }

    public function destroy(Request $request, Client $client, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()?->can('clients.delete'), 403);

        $auditLogger->deleted($client, 'clients', [
            'action' => 'client.deleted',
            'code' => $client->code,
            'name' => $client->fullName(),
        ]);

        $client->delete();

        return redirect()
            ->route('clients.index')
            ->with('status', 'Cliente eliminado correctamente.');
    }

    private function validateClient(Request $request, ?Client $client = null): array
    {
        return $request->validate([
            'agency_id' => ['nullable', 'exists:agencies,id'],
            'first_name' => ['required', 'string', 'max:120'],
            'middle_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'second_last_name' => ['nullable', 'string', 'max:120'],
            'married_name' => ['nullable', 'string', 'max:120'],
            'dpi' => [
                'required',
                'string',
                'min:13',
                'max:20',
                'regex:/^[0-9]+$/',
                Rule::unique('clients', 'dpi')->ignore($client?->id),
            ],
            'nit' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9]+$/'],
            'secondary_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9]+$/'],
            'email' => ['nullable', 'email', 'max:160'],
            'address_line' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'country' => ['required', 'string', 'max:80'],
            'occupation' => ['nullable', 'string', 'max:160'],
            'workplace' => ['nullable', 'string', 'max:180'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'agency_id.exists' => 'La agencia seleccionada no existe.',
            'first_name.required' => 'El primer nombre es obligatorio.',
            'last_name.required' => 'El primer apellido es obligatorio.',
            'dpi.required' => 'El DPI es obligatorio.',
            'dpi.min' => 'El DPI debe tener al menos 13 dígitos.',
            'dpi.regex' => 'El DPI solo puede contener números.',
            'dpi.unique' => 'Ya existe un cliente con ese DPI.',
            'nit.regex' => 'El NIT solo puede contener números.',
            'birth_date.before_or_equal' => 'La fecha de nacimiento no puede estar en el futuro.',
            'gender.in' => 'El género seleccionado no es válido.',
            'phone.required' => 'El teléfono principal es obligatorio.',
            'phone.regex' => 'El teléfono solo puede contener números.',
            'secondary_phone.regex' => 'El teléfono secundario solo puede contener números.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'address_line.required' => 'La dirección es obligatoria.',
            'country.required' => 'El país es obligatorio.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado seleccionado no es válido.',
            'notes.max' => 'Las observaciones no pueden superar 2000 caracteres.',
        ]);
    }

    private function generateUniqueCode(string $firstName, string $lastName): string
    {
        $prefix = Str::of($firstName.' '.$lastName)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '')
            ->limit(6, '')
            ->toString();

        if ($prefix === '') {
            $prefix = 'CLI';
        }

        $nextId = (Client::query()->withTrashed()->max('id') ?? 0) + 1;
        $code = 'CLI-'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT).'-'.$prefix;

        while (Client::query()->withTrashed()->where('code', $code)->exists()) {
            $nextId++;
            $code = 'CLI-'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT).'-'.$prefix;
        }

        return $code;
    }
}
