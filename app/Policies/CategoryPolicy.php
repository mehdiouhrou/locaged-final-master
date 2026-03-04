<?php
namespace App\Policies;
use App\Models\Category;
use App\Models\User;
class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        // CORRIGÉ : supprimé le fallback par rôle
        return $user->can('view any category');
    }
    public function view(User $user, Category $category): bool
    {
        return $this->viewAny($user);
    }
    public function create(User $user): bool
    {
        return $user->can('create category');
    }
    public function update(User $user, Category $category): bool
    {
        return $user->can('update category');
    }
    public function delete(User $user, Category $category): bool
    {
        return $user->can('delete category');
    }
    public function restore(User $user, Category $category): bool
    {
        return $user->can('restore category');
    }
    public function forceDelete(User $user, Category $category): bool
    {
        return $user->can('forceDelete category');
    }
}