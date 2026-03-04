<?php
namespace App\Policies;
use App\Models\PhysicalLocation;
use App\Models\User;
class PhysicalLocationPolicy
{
    public function viewAny(User $user): bool
    {
        // CORRIGÉ : supprimé tous les fallbacks par rôle
        return $user->can('view any physical location');
    }
    public function view(User $user, PhysicalLocation $physicalLocation): bool
    {
        return $this->viewAny($user);
    }
    public function create(User $user): bool
    {
        // CORRIGÉ : uniquement la permission Spatie
        // Seuls master, Directrice, Assistante ont 'create physical location'
        return $user->can('create physical location');
    }
    public function update(User $user, PhysicalLocation $physicalLocation): bool
    {
        // CORRIGÉ : uniquement la permission Spatie
        return $user->can('update physical location');
    }
    public function delete(User $user, PhysicalLocation $physicalLocation): bool
    {
        return $user->can('delete physical location');
    }
    public function restore(User $user, PhysicalLocation $physicalLocation): bool
    {
        return $user->can('restore physical location');
    }
    public function forceDelete(User $user, PhysicalLocation $physicalLocation): bool
    {
        return $user->can('forceDelete physical location');
    }
}