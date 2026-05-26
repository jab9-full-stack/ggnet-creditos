<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\User;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('users.view'), 403);

        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        $users = User::query()
            ->with(['agency:id,code,name', 'roles:id,name'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()?->can('users.create'), 403);

        return view('users.create', [
            'user' => new User(['status' => 'active']),
            'agencies' => Agency::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'selectedRoles' => [],
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()?->can('users.create'), 403);

        $data = $this->validateUser($request);

        $roles = $data['roles'] ?? [];
        unset($data['roles']);

        $data['password'] = Hash::make($data['password']);
        $data = $this->applyStatusFields($data, $request->user());

        $user = User::query()->create($data);
        $user->syncRoles($roles);

        $auditLogger->created($user, 'users', [
            'action' => 'user.created',
            'roles' => $roles,
        ]);

        return redirect()
            ->route('users.index')
            ->with('status', 'Usuario creado correctamente.');
    }

    public function edit(Request $request, User $user): View
    {
        abort_unless($request->user()?->can('users.update'), 403);

        return view('users.edit', [
            'user' => $user->load('roles'),
            'agencies' => Agency::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'selectedRoles' => $user->roles->pluck('name')->toArray(),
        ]);
    }

    public function update(Request $request, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()?->can('users.update'), 403);

        $data = $this->validateUser($request, $user);

        if ($request->user()?->is($user) && ($data['status'] ?? 'active') !== 'active') {
            return back()
                ->withInput()
                ->withErrors(['status' => 'No puedes desactivar tu propio usuario. Bonito intento de autosabotaje administrativo.']);
        }

        $roles = $data['roles'] ?? [];
        unset($data['roles']);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $data = $this->applyStatusFields($data, $request->user(), $user);

        $oldValues = $user->only(array_keys($data));
        $oldRoles = $user->getRoleNames()->toArray();

        $user->fill($data);
        $user->save();
        $user->syncRoles($roles);

        $auditLogger->updated(
            model: $user,
            oldValues: array_merge($oldValues, ['roles' => $oldRoles]),
            newValues: array_merge($user->fresh()->only(array_keys($data)), ['roles' => $roles]),
            module: 'users',
            context: [
                'action' => 'user.updated',
            ],
        );

        return redirect()
            ->route('users.index')
            ->with('status', 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()?->can('users.delete'), 403);

        if ($request->user()?->is($user)) {
            return redirect()
                ->route('users.index')
                ->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $auditLogger->deleted($user, 'users', [
            'action' => 'user.deleted',
            'roles' => $user->getRoleNames()->toArray(),
        ]);

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('status', 'Usuario eliminado correctamente.');
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'agency_id' => ['nullable', 'exists:agencies,id'],
            'name' => ['required', 'string', 'max:160'],
            'email' => [
                'required',
                'email',
                'max:180',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'password' => [
                $user ? 'nullable' : 'required',
                'string',
                'min:10',
                'confirmed',
            ],
            'status' => ['required', Rule::in(['active', 'inactive', 'blocked'])],
            'blocked_reason' => ['nullable', 'string', 'max:255'],
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ], [
            'agency_id.exists' => 'La agencia seleccionada no existe.',
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Ingresa un correo válido.',
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 10 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado seleccionado no es válido.',
            'roles.*.exists' => 'Uno de los roles seleccionados no existe.',
        ]);
    }

    private function applyStatusFields(array $data, ?User $actor, ?User $currentUser = null): array
    {
        if (($data['status'] ?? 'active') === 'blocked') {
            $data['blocked_at'] = $currentUser?->blocked_at ?? now();
            $data['blocked_by'] = $currentUser?->blocked_by ?? $actor?->id;
        } else {
            $data['blocked_at'] = null;
            $data['blocked_by'] = null;
            $data['blocked_reason'] = null;
        }

        return $data;
    }
}
