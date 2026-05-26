<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AgencyController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('agencies.view'), 403);

        $search = trim((string) $request->query('search', ''));

        $agencies = Agency::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('legal_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('agencies.index', [
            'agencies' => $agencies,
            'search' => $search,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()?->can('agencies.create'), 403);

        return view('agencies.create', [
            'agency' => new Agency([
                'country' => 'Guatemala',
                'is_active' => true,
            ]),
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()?->can('agencies.create'), 403);

        $data = $this->validateAgency($request);
        $data['code'] = $this->generateUniqueCode($data['name']);

        $agency = Agency::query()->create($data);

        $auditLogger->created($agency, 'agencies', [
            'action' => 'agency.created',
        ]);

        return redirect()
            ->route('agencies.index')
            ->with('status', 'Agencia creada correctamente.');
    }

    public function edit(Request $request, Agency $agency): View
    {
        abort_unless($request->user()?->can('agencies.update'), 403);

        return view('agencies.edit', [
            'agency' => $agency,
        ]);
    }

    public function update(Request $request, Agency $agency, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()?->can('agencies.update'), 403);

        $data = $this->validateAgency($request, $agency);
        $data['code'] = $this->generateUniqueCode($data['name'], $agency);

        $oldValues = $agency->only(array_keys($data));

        $agency->fill($data);
        $agency->save();

        $auditLogger->updated(
            model: $agency,
            oldValues: $oldValues,
            newValues: $agency->fresh()->only(array_keys($data)),
            module: 'agencies',
            context: [
                'action' => 'agency.updated',
            ],
        );

        return redirect()
            ->route('agencies.index')
            ->with('status', 'Agencia actualizada correctamente.');
    }

    public function destroy(Request $request, Agency $agency, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()?->can('agencies.delete'), 403);

        if ($agency->users()->exists()) {
            return redirect()
                ->route('agencies.index')
                ->with('error', 'No se puede eliminar una agencia con usuarios asignados.');
        }

        $auditLogger->deleted($agency, 'agencies', [
            'action' => 'agency.deleted',
        ]);

        $agency->delete();

        return redirect()
            ->route('agencies.index')
            ->with('status', 'Agencia eliminada correctamente.');
    }

    private function validateAgency(Request $request, ?Agency $agency = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'legal_name' => ['required', 'string', 'max:180'],
            'tax_id' => [
                'required',
                'string',
                'max:50',
                'regex:/^[0-9]+$/',
            ],
            'phone' => [
                'required',
                'string',
                'max:40',
                'regex:/^[0-9]+$/',
            ],
            'email' => ['nullable', 'email', 'max:160'],
            'address_line' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'country' => ['required', 'string', 'max:80'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'El nombre de agencia es obligatorio.',
            'legal_name.required' => 'La razón social es obligatoria.',
            'tax_id.required' => 'El NIT es obligatorio.',
            'tax_id.regex' => 'El NIT solo puede contener números.',
            'phone.required' => 'El teléfono es obligatorio.',
            'phone.regex' => 'El teléfono solo puede contener números.',
            'address_line.required' => 'La dirección es obligatoria.',
            'country.required' => 'El país es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
        ]) + [
            'is_active' => false,
        ];
    }

    private function generateUniqueCode(string $name, ?Agency $agency = null): string
    {
        $base = Str::of($name)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '_')
            ->trim('_')
            ->limit(24, '')
            ->toString();

        if ($base === '') {
            $base = 'AGENCIA';
        }

        $code = $base;
        $counter = 2;

        while (
            Agency::query()
                ->where('code', $code)
                ->when($agency, fn ($query) => $query->whereKeyNot($agency->id))
                ->exists()
        ) {
            $suffix = '_'.$counter;
            $code = Str::limit($base, 30 - strlen($suffix), '').$suffix;
            $counter++;
        }

        return $code;
    }
}
