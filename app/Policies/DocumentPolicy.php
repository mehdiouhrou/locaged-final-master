<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\Service;
use App\Models\SubDepartment;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if (
            $user->can('view any document') ||
            $user->can('view department document') ||
            $user->can('view service document') ||
            $user->can('view own document')
        ) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Document $document): bool
    {
        if ($user->can('view any document')) {
            return true;
        }

        /**
         * Special rule for Division Chief:
         *
         * They may only see a document if BOTH:
         *  - the document's department is one of their departments, AND
         *  - the document's service belongs to a sub-department that is also
         *    one of their sub-departments for that same department.
         */
        if ($user->can('view subdepartment scoped documents')) {
            // Departments assigned to the user
            $userDeptIds = ($user->relationLoaded('departments') || method_exists($user, 'departments'))
                ? $user->departments->pluck('id')->filter()
                : collect();

            // Sub-departments assigned to the user (pivot only)
            $userSubDeptIds = collect();
            if ($user->relationLoaded('subDepartments') || method_exists($user, 'subDepartments')) {
                $userSubDeptIds = $userSubDeptIds->merge($user->subDepartments->pluck('id'));
            }
            $userSubDeptIds = $userSubDeptIds->unique()->filter();

            if ($userDeptIds->isEmpty() || $userSubDeptIds->isEmpty()) {
                return false;
            }

            // Only keep sub-departments where BOTH the sub-department id and its department
            // belong to the user. This enforces the department+sub-department pair.
            $allowedSubDeptIds = SubDepartment::whereIn('id', $userSubDeptIds)
                ->whereIn('department_id', $userDeptIds)
                ->pluck('id');

            if ($allowedSubDeptIds->isEmpty()) {
                return false;
            }

            // Fetch services under those allowed sub-departments
            $serviceIds = Service::whereIn('sub_department_id', $allowedSubDeptIds)->pluck('id');
            if ($serviceIds->isEmpty()) {
                return false;
            }

            // Document is visible only if department & service match the allowed sets
            if (
                $userDeptIds->contains($document->department_id) &&
                $document->service_id &&
                $serviceIds->contains($document->service_id)
            ) {
                return true;
            }

            // For Division Chief, if the strict pair doesn't match, deny even if they
            // might have broader generic permissions.
            return false;
        }

        if ($user->can('view service document')) {
            // Build list of services the user is related to (pivot + via sub-departments)
            $visibleServiceIds = collect();

            // Services directly assigned (pivot)
            if ($user->relationLoaded('services') || method_exists($user, 'services')) {
                $visibleServiceIds = $visibleServiceIds->merge($user->services->pluck('id'));
            }

            // Sub-departments (pivot only) -> services under those sub-departments
            $subDeptIds = collect();
            if ($user->relationLoaded('subDepartments') || method_exists($user, 'subDepartments')) {
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

        if ($user->can('view department document') && $user->departments->pluck('id')->contains($document->department_id)) {
            return true;
        }

        if ($user->can('view own document') && $user->id === $document->created_by) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create document') || $user->can('upload document');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Document $document): bool
    {
        if ($user->cannot('update document')) {
            return false;
        }

        // Decision 28/07/2026 : un document collaboratif verrouillé (catégorie déjà
        // assignée, en attente d'archivage ou archivé) n'est plus modifiable par
        // personne, sauf master ('manage document global expiry' est une permission
        // exclusive à master, utilisée ici comme marqueur car hasRole() est proscrit
        // dans les Policies). Logique d'expiration gérée séparément ailleurs.
        if (in_array($document->status, ['valide', 'attente_archivage', 'approved', 'archived'], true)) {
            return $user->can('manage document global expiry');
        }

        if ($user->can('view any document')) {
            return true;
        }

        // Service-level users (Admin de cellule, Service Manager, etc.)
        if ($user->can('view service document')) {
            // Build list of services the user is related to
            $visibleServiceIds = collect();

            // Services directly assigned (pivot)
            if ($user->relationLoaded('services') || method_exists($user, 'services')) {
                $visibleServiceIds = $visibleServiceIds->merge($user->services->pluck('id'));
            }

            // Sub-departments (pivot only) -> services under those sub-departments
            $subDeptIds = collect();
            if ($user->relationLoaded('subDepartments') || method_exists($user, 'subDepartments')) {
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

        if ($user->can('view department document') && $user->departments->pluck('id')->contains($document->department_id)) {
            return true;
        }

        if ($user->can('view own document') && $user->id === $document->created_by) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Document $document): bool
    {
        if ($user->cannot('delete document')) {
            return false;
        }

        // Decision 28/07/2026 : un document verrouillé (attente_archivage/approved/archived)
        // ne peut être supprimé que par master, sauf s'il est expiré (logique d'expiration
        // gérée séparément et prioritaire sur le verrouillage).
        $isLocked = in_array($document->status, ['valide', 'attente_archivage', 'approved', 'archived'], true);
        if ($isLocked && ! $this->isDocumentExpired($document)) {
            return $user->can('manage document global expiry');
        }

        if ($user->can('view any role') && $user->can('delete document')) {
            return true;
        }

        if ($user->can('view any document')) {
            return true;
        }

        if ($user->can('view department document') && $user->departments->pluck('id')->contains($document->department_id)) {
            // Chef de Pôle: suppression limitée aux documents expirés.
            return $this->isDocumentExpired($document);
        }

        if ($user->can('view own document') && $user->id === $document->created_by) {
            return $this->isDocumentExpired($document);
        }
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Document $document): bool
    {
        if ($user->cannot('restore document')) {
            return false;
        }

        if ($user->can('view any document')) {
            return true;
        }

        if ($user->can('view department document') && $user->departments->pluck('id')->contains($document->department_id)) {
            return true;
        }

        if ($user->can('view own document') && $user->id === $document->created_by) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Document $document): bool
    {
        if ($user->cannot('forceDelete document')) {
            return false;
        }

        if ($user->can('view any document')) {
            return true;
        }

        if ($user->can('view department document') && $user->departments->pluck('id')->contains($document->department_id)) {
            return true;
        }

        if ($user->can('view own document') && $user->id === $document->created_by) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can approve documents.
     */
    public function approve(User $user): bool
    {
        return $user->can('approve document');
    }

    /**
     * Determine whether the user can decline documents.
     */
    public function decline(User $user): bool
    {
        return $user->can('decline document');
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

    /**
     * Determine whether the user can permanently delete a document.
     *
     * Only master, super admin, and admin de pôle roles are allowed.
     * Additionally, the document creator can delete their own declined documents.
     */
    public function permanentDelete(User $user, Document $document): bool
    {
        // Decision 28/07/2026 : un document verrouillé (attente_archivage/approved/archived)
        // ne peut être supprimé définitivement que par master, sauf s'il est expiré.
        $isLocked = in_array($document->status, ['valide', 'attente_archivage', 'approved', 'archived'], true);
        if ($isLocked && ! $this->isDocumentExpired($document)) {
            return $user->can('manage document global expiry');
        }

        if ($user->can('view any role')
            || $user->can('view organization wide reports')
            || $user->can('view any department')) {
            return true;
        }

        // Allow document creator to permanently delete their own declined documents
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
