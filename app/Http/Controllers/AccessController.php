<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LogsFluxioActivity;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AccessController extends Controller
{
    use LogsFluxioActivity;

    private const ADMIN_ROLE = 'Administrador';

    public function users(): Response
    {
        $users = User::query()
            ->with('roles')
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'role_name' => $user->roles->first()?->name,
                'role_id' => $user->roles->first()?->id,
                'is_active' => $user->is_active,
            ]);
        $roles = Role::query()->orderBy('name')->get();

        return Inertia::render('Access/Users', [
            'records' => $users->items(),
            'pagination' => $this->paginationMeta($users),
            'roles' => $roles->map(fn (Role $role): array => [
                'id' => $role->id,
                'label' => $role->name,
            ])->values(),
            'defaults' => [
                'is_active' => true,
            ],
            'endpoints' => [
                'store' => '/gestao-de-acessos/utilizadores',
                'update' => '/gestao-de-acessos/utilizadores',
                'delete' => '/gestao-de-acessos/utilizadores',
            ],
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'mobile' => ['nullable', 'string', 'max:255'],
            'role_id' => ['nullable', 'exists:roles,id'],
            'password' => ['nullable', 'string', 'min:12'],
            'is_active' => ['boolean'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'mobile' => $validated['mobile'] ?? null,
            'password' => Hash::make($validated['password'] ?? Str::password(16)),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        if (! empty($validated['role_id'])) {
            $role = Role::query()->find($validated['role_id']);

            if ($role) {
                $user->syncRoles([$role->name]);
            }
        }

        $this->logFluxioActivity($request, 'Utilizadores', 'create', $user);

        return back()->with($this->flashToast('success', 'Utilizador criado com sucesso.'));
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'mobile' => ['nullable', 'string', 'max:255'],
            'role_id' => ['nullable', 'exists:roles,id'],
            'password' => ['nullable', 'string', 'min:12'],
            'is_active' => ['boolean'],
        ]);

        $newRole = ! empty($validated['role_id'])
            ? Role::query()->find($validated['role_id'])
            : null;
        $newIsActive = (bool) ($validated['is_active'] ?? true);

        $this->ensureUserChangeKeepsAdminAccess($request, $user, $newRole, $newIsActive);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'mobile' => $validated['mobile'] ?? null,
            'is_active' => $newIsActive,
        ]);

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        if ($newRole) {
            $user->syncRoles([$newRole->name]);
        } else {
            $user->syncRoles([]);
        }

        $this->logFluxioActivity($request, 'Utilizadores', 'update', $user);

        return back()->with($this->flashToast('success', 'Utilizador atualizado com sucesso.'));
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->is($user)) {
            throw ValidationException::withMessages([
                'user' => 'Não pode remover o seu próprio utilizador.',
            ]);
        }

        if ($this->wouldRemoveLastAdmin($user, null, false)) {
            throw ValidationException::withMessages([
                'user' => 'Nao pode remover o ultimo administrador ativo.',
            ]);
        }

        $user->delete();

        $this->logFluxioActivity($request, 'Utilizadores', 'delete');

        return back()->with($this->flashToast('success', 'Utilizador removido com sucesso.'));
    }

    public function permissionGroups(): Response
    {
        $catalogue = $this->permissionCatalogue();
        $this->ensurePermissionsExist($catalogue);

        $roles = Role::query()->orderBy('name')->get();

        return Inertia::render('Access/Permissions', [
            'records' => $roles->map(function (Role $role): array {
                $permissions = $role->permissions->pluck('name')->values()->all();

                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'is_active' => (bool) ($role->is_active ?? true),
                    'users_count' => User::role($role->name)->count(),
                    'permissions' => $permissions,
                ];
            })->values(),
            'permissionModules' => $catalogue,
            'defaults' => [
                'is_active' => true,
                'permissions' => [],
            ],
            'endpoints' => [
                'store' => '/gestao-de-acessos/permissoes',
                'update' => '/gestao-de-acessos/permissoes',
                'delete' => '/gestao-de-acessos/permissoes',
            ],
        ]);
    }

    public function storePermissionGroup(Request $request): RedirectResponse
    {
        $catalogue = $this->permissionCatalogue();
        $this->ensurePermissionsExist($catalogue);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'is_active' => ['boolean'],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in($this->validPermissionNames($catalogue))],
        ]);

        $role = Role::query()->create([
            'name' => $validated['name'],
            'guard_name' => 'web',
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        $this->logFluxioActivity($request, 'Permissões', 'create', $role);

        return back()->with($this->flashToast('success', 'Grupo de permissões criado com sucesso.'));
    }

    public function updatePermissionGroup(Request $request, Role $role): RedirectResponse
    {
        $catalogue = $this->permissionCatalogue();
        $this->ensurePermissionsExist($catalogue);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'is_active' => ['boolean'],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in($this->validPermissionNames($catalogue))],
        ]);

        $role->update([
            'name' => $validated['name'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        $this->logFluxioActivity($request, 'Permissões', 'update', $role);

        return back()->with($this->flashToast('success', 'Grupo de permissões atualizado com sucesso.'));
    }

    public function destroyPermissionGroup(Request $request, Role $role): RedirectResponse
    {
        $role->delete();

        $this->logFluxioActivity($request, 'Permissões', 'delete');

        return back()->with($this->flashToast('success', 'Grupo de permissões removido com sucesso.'));
    }

    private function permissionCatalogue(): array
    {
        return [
            ['slug' => 'dashboard', 'label' => 'Dashboard', 'abilities' => ['read']],
            ['slug' => 'clientes', 'label' => 'Clientes', 'abilities' => ['create', 'read', 'update', 'delete']],
            ['slug' => 'fornecedores', 'label' => 'Fornecedores', 'abilities' => ['create', 'read', 'update', 'delete']],
            ['slug' => 'contactos', 'label' => 'Contactos', 'abilities' => ['create', 'read', 'update', 'delete']],
            ['slug' => 'propostas', 'label' => 'Propostas', 'abilities' => ['create', 'read', 'update', 'delete']],
            ['slug' => 'encomendas-clientes', 'label' => 'Encomendas - Clientes', 'abilities' => ['create', 'read', 'update', 'delete']],
            ['slug' => 'encomendas-fornecedores', 'label' => 'Encomendas - Fornecedores', 'abilities' => ['create', 'read', 'update', 'delete']],
            ['slug' => 'faturas-fornecedores', 'label' => 'Faturas Fornecedores', 'abilities' => ['create', 'read', 'update', 'delete']],
            ['slug' => 'ordens-trabalho', 'label' => 'Ordens de Trabalho', 'abilities' => ['create', 'read', 'update', 'delete']],
            ['slug' => 'financeiro', 'label' => 'Financeiro', 'abilities' => ['read']],
            ['slug' => 'contas-bancarias', 'label' => 'Contas Bancárias', 'abilities' => ['create', 'read', 'update', 'delete']],
            ['slug' => 'conta-corrente-clientes', 'label' => 'Conta Corrente Clientes', 'abilities' => ['read']],
            ['slug' => 'arquivo-digital', 'label' => 'Arquivo Digital', 'abilities' => ['create', 'read', 'update', 'delete']],
            ['slug' => 'utilizadores', 'label' => 'Utilizadores', 'abilities' => ['create', 'read', 'update', 'delete']],
            ['slug' => 'permissoes', 'label' => 'Permissões', 'abilities' => ['create', 'read', 'update', 'delete']],
            ['slug' => 'configuracoes', 'label' => 'Configurações', 'abilities' => ['create', 'read', 'update', 'delete']],
            ['slug' => 'workspace', 'label' => 'Workspace', 'abilities' => ['read']],
            ['slug' => 'artigos', 'label' => 'Artigos', 'abilities' => ['create', 'read', 'update', 'delete']],
            ['slug' => 'empresa', 'label' => 'Empresa', 'abilities' => ['create', 'read', 'update', 'delete']],
            ['slug' => 'calendario', 'label' => 'Calendário', 'abilities' => ['create', 'read', 'update', 'delete']],
            ['slug' => 'logs', 'label' => 'Logs', 'abilities' => ['read']],
        ];
    }

    private function ensurePermissionsExist(array $catalogue): void
    {
        foreach ($catalogue as $module) {
            foreach ($module['abilities'] as $ability) {
                Permission::findOrCreate(sprintf('%s.%s', $module['slug'], $ability), 'web');
            }
        }
    }

    private function validPermissionNames(array $catalogue): array
    {
        return collect($catalogue)
            ->flatMap(fn (array $module) => collect($module['abilities'])
                ->map(fn (string $ability): string => sprintf('%s.%s', $module['slug'], $ability)))
            ->values()
            ->all();
    }

    private function ensureUserChangeKeepsAdminAccess(Request $request, User $user, ?Role $newRole, bool $newIsActive): void
    {
        if ($request->user()?->is($user) && ! $newIsActive) {
            throw ValidationException::withMessages([
                'is_active' => 'Nao pode desativar a sua propria conta.',
            ]);
        }

        if ($request->user()?->is($user) && $this->isAdminUser($user) && $newRole?->name !== self::ADMIN_ROLE) {
            throw ValidationException::withMessages([
                'role_id' => 'Nao pode remover o seu proprio acesso de administracao.',
            ]);
        }

        if ($this->wouldRemoveLastAdmin($user, $newRole, $newIsActive)) {
            throw ValidationException::withMessages([
                'role_id' => 'Nao pode deixar o sistema sem um administrador ativo.',
            ]);
        }
    }

    private function isAdminUser(User $user): bool
    {
        return $user->hasRole(self::ADMIN_ROLE);
    }

    private function activeAdminCount(): int
    {
        return User::role(self::ADMIN_ROLE)
            ->where('is_active', true)
            ->count();
    }

    private function wouldRemoveLastAdmin(User $user, ?Role $newRole, bool $newIsActive): bool
    {
        if (! $this->isAdminUser($user) || ! $user->is_active) {
            return false;
        }

        if ($this->activeAdminCount() > 1) {
            return false;
        }

        return ! $newIsActive || $newRole?->name !== self::ADMIN_ROLE;
    }
}
