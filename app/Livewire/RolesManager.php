<?php

namespace App\Livewire;

use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;

class RolesManager extends Component
{
    // Liste
    public $roles;
    public $permissionGroups = [];

    // Formulaire
    public $showForm = false;
    public $editingRoleId = null;
    public $roleName = '';
    public $roleDisplayName = '';
    public $selectedPermissions = [];

    // Confirmation suppression
    public $confirmDeleteId = null;
    public $confirmDeleteName = '';

    // Messages
    public $successMessage = '';
    public $errorMessage = '';

    /**
     * Permissions réservées au master uniquement
     */
    protected array $masterOnlyPermissions = [
        'manage roles',
        'manage permissions',
        'manage settings',
        'view master console',
        'manage document global expiry',
        'manage any user',
        'impersonate user',
        'view any role',
    ];

    /**
     * Rôles qu'un non-master ne peut pas modifier ni supprimer
     */
    protected array $protectedRoles = [
        'master',
    ];

    public function mount(): void
    {
        $this->roles = collect();
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->roles = Role::withCount('permissions', 'users')->orderBy('name')->get();

        $perms = Permission::orderBy('name')->get();
        $groups = [];
        foreach ($perms as $perm) {
            $parts = explode(' ', $perm->name);
            $group = end($parts);
            if (!isset($groups[$group])) {
                $groups[$group] = [];
            }
            $groups[$group][] = [
                'id'   => $perm->id,
                'name' => $perm->name,
            ];
        }
        ksort($groups);
        $this->permissionGroups = $groups;
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
        $this->editingRoleId = null;
    }

    public function openEdit(int $roleId): void
    {
        $role = Role::with('permissions')->findOrFail($roleId);

        if (!$this->canManageRole($role->name)) {
            $this->errorMessage = 'Vous ne pouvez pas modifier ce rôle.';
            return;
        }

        $this->resetForm();
        $this->editingRoleId = $roleId;
        $this->roleName = $role->name;
        $this->roleDisplayName = $role->display_name ?? '';
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'roleName' => 'required|string|min:2|max:100',
        ]);

        // Filtrer les permissions master-only si l'utilisateur n'est pas master
        $filteredPermissions = $this->selectedPermissions;
        if (!Auth::user()->hasRole('master')) {
            $filteredPermissions = array_values(array_filter(
                $this->selectedPermissions,
                fn($p) => !in_array($p, $this->masterOnlyPermissions, true)
            ));
        }

        try {
            if ($this->editingRoleId) {
                $role = Role::findOrFail($this->editingRoleId);

                if (!$this->canManageRole($role->name)) {
                    $this->errorMessage = 'Vous ne pouvez pas modifier ce rôle.';
                    return;
                }

                $role->name = $this->roleName;
                if (array_key_exists('display_name', $role->getAttributes())) {
                    $role->display_name = $this->roleDisplayName;
                }
                $role->save();
                $role->syncPermissions($filteredPermissions);

                $this->successMessage = 'Rôle « ' . $role->name . ' » mis à jour.';
            } else {
                if (!Auth::user()->can('manage roles')) {
                    $this->errorMessage = "Vous n'avez pas la permission de créer des rôles.";
                    return;
                }

                $attributes = ['name' => $this->roleName, 'guard_name' => 'web'];
                $role = Role::create($attributes);
                $role->syncPermissions($filteredPermissions);

                $this->successMessage = 'Rôle « ' . $role->name . ' » créé.';
            }

            $this->loadData();
            $this->showForm = false;
            $this->resetForm();

        } catch (\Exception $e) {
            $this->errorMessage = 'Erreur : ' . $e->getMessage();
        }
    }

    public function confirmDelete(int $roleId): void
    {
        $role = Role::findOrFail($roleId);

        if (!$this->canManageRole($role->name)) {
            $this->errorMessage = 'Vous ne pouvez pas supprimer ce rôle.';
            return;
        }

        $this->confirmDeleteId = $roleId;
        $this->confirmDeleteName = $role->name;
    }

    public function deleteRole(): void
    {
        if (!$this->confirmDeleteId) {
            return;
        }

        $role = Role::find($this->confirmDeleteId);
        if (!$role) {
            $this->confirmDeleteId = null;
            return;
        }

        if (!$this->canManageRole($role->name)) {
            $this->errorMessage = 'Vous ne pouvez pas supprimer ce rôle.';
            $this->confirmDeleteId = null;
            return;
        }

        $name = $role->name;
        $role->delete();

        $this->successMessage = 'Rôle « ' . $name . ' » supprimé.';
        $this->confirmDeleteId = null;
        $this->confirmDeleteName = '';
        $this->loadData();
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
        $this->confirmDeleteName = '';
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function togglePermission(string $permName): void
    {
        // Bloquer les permissions master-only pour les non-master
        if (!Auth::user()->hasRole('master') && in_array($permName, $this->masterOnlyPermissions, true)) {
            return;
        }

        if (in_array($permName, $this->selectedPermissions, true)) {
            $this->selectedPermissions = array_values(
                array_filter($this->selectedPermissions, fn($p) => $p !== $permName)
            );
        } else {
            $this->selectedPermissions[] = $permName;
        }
    }

    public function clearMessage(): void
    {
        $this->successMessage = '';
        $this->errorMessage = '';
    }

    public function isMasterOnly(string $permName): bool
    {
        return in_array($permName, $this->masterOnlyPermissions, true);
    }

    public function isProtectedRole(string $roleName): bool
    {
        return in_array($roleName, $this->protectedRoles, true);
    }

    private function canManageRole(string $roleName): bool
    {
        if (Auth::user()->hasRole('master')) {
            return true;
        }
        return !in_array($roleName, $this->protectedRoles, true);
    }

    private function resetForm(): void
    {
        $this->roleName = '';
        $this->roleDisplayName = '';
        $this->selectedPermissions = [];
        $this->errorMessage = '';
        $this->successMessage = '';
    }

    public function render()
    {
        return view('livewire.roles-manager');
    }
}
