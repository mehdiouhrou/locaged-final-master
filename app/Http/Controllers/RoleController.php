<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    private array $masterOnlyPermissions = [
        'manage permissions',
        'manage settings',
        'view master console',
        'manage document global expiry',
        'manage any user',
        'impersonate user',
        'view any role',
        'access horizon',
        'view server',
        'access management sidebar',
        'access document expiration management',
        'destroy expired document',
        'postpone document expiration',
    ];

    private array $scopeMap = [
        'any'           => ['view any document'],
        'department'    => ['view department document'],
        'subdepartment' => ['view subdepartment scoped documents'],
        'service'       => ['view service document'],
        'own'           => ['view own document'],
    ];

    private array $allScopePermissions = [
        'view any document',
        'view department document',
        'view subdepartment scoped documents',
        'view service document',
        'view own document',
    ];

    public function index()
    {
        Gate::authorize('viewAny', Role::class);
        $roles = Role::withCount('permissions', 'users')->orderBy('name')->get();
        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        Gate::authorize('create', Role::class);
        return view('roles.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Role::class);
        $request->validate([
            'name'       => ['required', 'string', 'max:255', 'unique:roles,name'],
            'scope'      => ['nullable', 'string', 'in:any,department,subdepartment,service,own'],
            'actions'    => ['nullable', 'array'],
            'actions.*'  => ['string'],
        ]);
        $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);
        $permissions = $this->resolvePermissions($request->scope, $request->actions ?? []);
        $role->syncPermissions($this->filterAndValidatePermissions($permissions));
        return redirect()->route('roles.index')->with('success', 'Rôle créé avec succès.');
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);
        Gate::authorize('update', $role);
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        return view('roles.edit', compact('role', 'rolePermissions'));
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        Gate::authorize('update', $role);
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'scope'      => ['nullable', 'string', 'in:any,department,subdepartment,service,own'],
            'actions'    => ['nullable', 'array'],
            'actions.*'  => ['string'],
        ]);
        if ($role->name !== 'master') {
            $role->name = $validated['name'];
            $role->save();
        }
        $permissions = $this->resolvePermissions(
            $validated['scope'] ?? null,
            $validated['actions'] ?? []
        );
        $role->syncPermissions($this->filterAndValidatePermissions($permissions));
        return redirect()->route('roles.index')->with('success', 'Rôle mis à jour avec succès.');
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        Gate::authorize('delete', $role);
        $role->delete();
        return redirect()->route('roles.index')->with('success', 'Rôle supprimé.');
    }

    private function resolvePermissions(?string $scope, array $actions): array
    {
        $scopePerms = [];
        if ($scope && isset($this->scopeMap[$scope])) {
            $scopePerms = $this->scopeMap[$scope];
        }
        $actionPerms = [];
        foreach ($actions as $actionValue) {
            foreach (explode(',', $actionValue) as $perm) {
                $perm = trim($perm);
                if ($perm !== '') {
                    $actionPerms[] = $perm;
                }
            }
        }
        return array_unique(array_merge($scopePerms, $actionPerms));
    }

    private function filterAndValidatePermissions(array $permissions): array
    {
        $isMaster = auth()->user()->hasRole('master');
        if (!$isMaster) {
            $permissions = array_values(array_filter(
                $permissions,
                fn($p) => !in_array($p, $this->masterOnlyPermissions, true)
            ));
        }
        $existingPerms = Permission::whereIn('name', $permissions)
            ->pluck('name')
            ->toArray();
        return $existingPerms;
    }
}
