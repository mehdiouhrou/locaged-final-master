<?php
namespace App\Policies;
use App\Models\DocumentDestructionRequest;
use App\Models\User;
class DocumentDestructionRequestPolicy
{
    public function viewAny(User $user): bool
    {
        // CORRIGÉ : supprimé les hasRole en dur
        return $user->can('view any document destruction request')
            || $user->can('view department document destruction request')
            || $user->can('view own document destruction request');
    }
    public function view(User $user, DocumentDestructionRequest $destructionRequest): bool
    {
        if ($user->can('view any document destruction request')) {
            return true;
        }
        if ($user->can('view department document destruction request')
            && $user->departments->pluck('id')->contains($destructionRequest->document->department_id)) {
            return true;
        }
        if ($user->can('view own document destruction request')
            && $user->id === $destructionRequest->document->created_by) {
            return true;
        }
        return false;
    }
    public function create(User $user): bool
    {
        return $user->can('create document destruction request');
    }
    public function update(User $user, DocumentDestructionRequest $destructionRequest): bool
    {
        if ($user->cannot('update document destruction request')) {
            return false;
        }
        if ($user->can('view any document destruction request')) {
            return true;
        }
        if ($user->can('view department document destruction request')
            && $user->departments->pluck('id')->contains($destructionRequest->document->department_id)) {
            return true;
        }
        if ($user->can('view own document destruction request')
            && $user->id === $destructionRequest->document->created_by) {
            return true;
        }
        return false;
    }
    public function delete(User $user, DocumentDestructionRequest $destructionRequest): bool
    {
        if ($user->cannot('delete document destruction request')) {
            return false;
        }
        if ($user->can('view any document destruction request')) {
            return true;
        }
        if ($user->can('view department document destruction request')
            && $user->departments->pluck('id')->contains($destructionRequest->document->department_id)) {
            return true;
        }
        if ($user->can('view own document destruction request')
            && $user->id === $destructionRequest->document->created_by) {
            return true;
        }
        return false;
    }
    public function restore(User $user, DocumentDestructionRequest $destructionRequest): bool
    {
        if ($user->cannot('restore document destruction request')) {
            return false;
        }
        if ($user->can('view any document destruction request')) {
            return true;
        }
        if ($user->can('view department document destruction request')
            && $user->departments->pluck('id')->contains($destructionRequest->document->department_id)) {
            return true;
        }
        if ($user->can('view own document destruction request')
            && $user->id === $destructionRequest->document->created_by) {
            return true;
        }
        return false;
    }
    public function forceDelete(User $user, DocumentDestructionRequest $destructionRequest): bool
    {
        if ($user->cannot('forceDelete document destruction request')) {
            return false;
        }
        if ($user->can('view any document destruction request')) {
            return true;
        }
        if ($user->can('view department document destruction request')
            && $user->departments->pluck('id')->contains($destructionRequest->document->department_id)) {
            return true;
        }
        if ($user->can('view own document destruction request')
            && $user->id === $destructionRequest->document->created_by) {
            return true;
        }
        return false;
    }
    public function approve(User $user): bool
    {
        return $user->can('approve document destruction request');
    }
    public function decline(User $user): bool
    {
        return $user->can('decline document destruction request');
    }
    public function postpone(User $user): bool
    {
        // CORRIGÉ : utilise la permission approve comme proxy
        // (seuls ceux qui peuvent approuver peuvent aussi reporter)
        return $user->can('approve document destruction request');
    }
}