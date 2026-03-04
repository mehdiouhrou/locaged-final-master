<?php
namespace App\Policies;
use App\Models\Tag;
use App\Models\User;
class TagPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view any tag');
    }
    public function view(User $user, Tag $tag): bool
    {
        return $user->can('view any tag');
    }
    public function create(User $user): bool
    {
        // CORRIGÉ : supprimé la liste de rôles en dur, utilise la permission
        return $user->can('create tag');
    }
    public function update(User $user, Tag $tag): bool
    {
        return $user->can('update tag');
    }
    public function delete(User $user, Tag $tag): bool
    {
        return $user->can('delete tag');
    }
    public function restore(User $user, Tag $tag): bool
    {
        return $user->can('restore tag');
    }
    public function forceDelete(User $user, Tag $tag): bool
    {
        return $user->can('forceDelete tag');
    }
}