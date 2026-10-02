<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\Service;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view any document')
            || $user->can('view department document')
            || $user->can('view service document')
            || $user->can('view own document');
    }

    public function view(User $user, Document $document): bool
    {
        // Bypass : admins voient tout
        if ($user->can('view any document')) {
            return true;
        }

        // Le créateur voit toujours son propre document
        if ($document->created_by === $user->id) {
            return true;
        }

        // Accès hiérarchique département → doc approuvé suffit
        if ($user->can('view department document')) {
            return in_array($document->status, ['approved', 'archived']);
        }

        // Accès hiérarchique département → doc approuvé suffit
        if ($user->can('view department document')) {
            return in_array($document->status, ['approved', 'archived']);
        }

        // Seuls approved/archived visibles pour les autres
        if (!in_array($document->status, ['approved', 'archived'])) {
            return false;
        }

        // Accès par catégorie directement assignée à l'utilisateur
        $categoryIds = $user->accessibleCategories()->pluck('categories.id');
        if ($document->category_id && $categoryIds->contains($document->category_id)) {
            return true;
        }

        // Accès par sous-catégorie directement assignée
        $subcategoryIds = $user->accessibleSubcategories()->pluck('subcategories.id');
        if ($document->subcategory_id && $subcategoryIds->contains($document->subcategory_id)) {
            return true;
        }

        // Doc dont la catégorie parente est accessible via une sous-catégorie assignée
        if ($document->category_id && $subcategoryIds->isNotEmpty()) {
            $parentCatIds = \App\Models\Subcategory::whereIn('id', $subcategoryIds)
                ->pluck('category_id');
            if ($parentCatIds->contains($document->category_id)) {
                return true;
            }
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('create document') || $user->can('upload document');
    }

    public function update(User $user, Document $document): bool
    {
        if ($user->cannot('update document')) {
            return false;
        }

        if (in_array($document->status, ['valide', 'attente_archivage', 'approved', 'archived'], true)) {
            return $user->can('manage document global expiry');
        }

        if ($user->can('view any document')) {
            return true;
        }

        if ($user->can('view service document')) {
            $visibleServiceIds = collect();
            if (method_exists($user, 'services')) {
                $visibleServiceIds = $visibleServiceIds->merge($user->services->pluck('id'));
            }
            $subDeptIds = collect();
            if (method_exists($user, 'subDepartments')) {
                $subDeptIds = $subDeptIds->merge($user->subDepartments->pluck('id'));
            }
            $subDeptIds = $subDeptIds->unique()->filter();
            if ($subDeptIds->isNotEmpty()) {
                $visibleServiceIds = $visibleServiceIds->merge(
                    Service::whereIn('sub_department_id', $subDeptIds)->pluck('id')
                );
            }
            $visibleServiceIds = $visibleServiceIds->unique()->filter();
            if ($document->service_id && $visibleServiceIds->contains($document->service_id)) {
                return true;
            }
        }

        if ($user->can('view department document') && $user->departments()->pluck('departments.id')->contains($document->department_id)) {
            return true;
        }

        if ($user->can('view own document') && $user->id === $document->created_by) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Document $document): bool
    {
        if ($user->cannot('delete document')) {
            return false;
        }

        $isLocked = in_array($document->status, ['valide', 'attente_archivage', 'approved', 'archived'], true);
        if ($isLocked && !$this->isDocumentExpired($document)) {
            return $user->can('manage document global expiry');
        }

        if ($user->can('view any role') && $user->can('delete document')) {
            return true;
        }

        if ($user->can('view any document')) {
            return true;
        }

        if ($user->can('view department document') && $user->departments()->pluck('departments.id')->contains($document->department_id)) {
            return $this->isDocumentExpired($document);
        }

        if ($user->can('view own document') && $user->id === $document->created_by) {
            return $this->isDocumentExpired($document);
        }

        return false;
    }

    public function restore(User $user, Document $document): bool
    {
        if ($user->cannot('restore document')) {
            return false;
        }

        if ($user->can('view any document')) {
            return true;
        }

        if ($user->can('view department document') && $user->departments()->pluck('departments.id')->contains($document->department_id)) {
            return true;
        }

        if ($user->can('view own document') && $user->id === $document->created_by) {
            return true;
        }

        return false;
    }

    public function forceDelete(User $user, Document $document): bool
    {
        if ($user->cannot('forceDelete document')) {
            return false;
        }

        if ($user->can('view any document')) {
            return true;
        }

        if ($user->can('view department document') && $user->departments()->pluck('departments.id')->contains($document->department_id)) {
            return true;
        }

        if ($user->can('view own document') && $user->id === $document->created_by) {
            return true;
        }

        return false;
    }

    public function approve(User $user): bool
    {
        return $user->can('approve document');
    }

    public function decline(User $user): bool
    {
        return $user->can('decline document');
    }

    public function move(User $user, Document $document): bool
    {
        return $user->can('move document');
    }

    public function download(User $user, Document $document): bool
    {
        if ($user->cannot('download document')) {
            return false;
        }

        if ($document->status !== 'approved') {
            return false;
        }

        return $this->view($user, $document);
    }

    public function permanentDelete(User $user, Document $document): bool
    {
        $isLocked = in_array($document->status, ['valide', 'attente_archivage', 'approved', 'archived'], true);
        if ($isLocked && !$this->isDocumentExpired($document)) {
            return $user->can('manage document global expiry');
        }

        if ($user->can('view any role')
            || $user->can('view organization wide reports')
            || $user->can('view any department')
            || $user->can('destroy expired document')) {
            return true;
        }

        if ($document->status === 'declined' && $document->created_by === $user->id) {
            return true;
        }

        return false;
    }

    private function isDocumentExpired(Document $document): bool
    {
        if ((bool) $document->is_expired) {
            return true;
        }

        if ($document->expire_at === null) {
            return false;
        }

        return now()->isAfter($document->expire_at);
    }
}
