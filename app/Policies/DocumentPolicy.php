<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\Service;
use App\Models\SubDepartment;
use App\Models\User;

class DocumentPolicy
{
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

    public function view(User $user, Document $document): bool
    {
        // Seuls les docs approved ou archived sont visibles
        if (!in_array($document->status, ['approved', 'archived'])) {
            if ($user->can('view any document')) {
                return true; // admins voient tout
            }
            return false;
        }

        if ($user->can('view any document')) {
            return true;
        }

        if ($user->can('view subdepartment scoped documents')) {
            $userDeptIds = ($user->relationLoaded('departments') || method_exists($user, 'departments'))
                ? $user->departments->pluck('id')->filter()
                : collect();

            $userSubDeptIds = collect();
            if ($user->relationLoaded('subDepartments') || method_exists($user, 'subDepartments')) {
                $userSubDeptIds = $userSubDeptIds->merge($user->subDepartments->pluck('id'));
            }
            $userSubDeptIds = $userSubDeptIds->unique()->filter();

            if ($userDeptIds->isEmpty() || $userSubDeptIds->isEmpty()) {
                return false;
            }

            $allowedSubDeptIds = SubDepartment::whereIn('id', $userSubDeptIds)
                ->whereIn('department_id', $userDeptIds)
                ->pluck('id');

            if ($allowedSubDeptIds->isEmpty()) {
                return false;
            }

            $serviceIds = Service::whereIn('sub_department_id', $allowedSubDeptIds)->pluck('id');
            if ($serviceIds->isEmpty()) {
                return false;
            }

            // Doc dans le département sans service précis → ok
            if ($userDeptIds->contains($document->department_id) && !$document->service_id) {
                return true;
            }

            // Doc avec service → vérifier qu'il est dans un service du pôle
            if (
                $userDeptIds->contains($document->department_id) &&
                $document->service_id &&
                $serviceIds->contains($document->service_id)
            ) {
                return true;
            }

            return false;
        }

        if ($user->can('view service document')) {
            $visibleServiceIds = collect();

            if ($user->relationLoaded('services') || method_exists($user, 'services')) {
                $visibleServiceIds = $visibleServiceIds->merge($user->services->pluck('id'));
            }

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

        if ($user->can('view department document') && $user->departments()->pluck('departments.id')->contains($document->department_id)) {
            return true;
        }

        if ($user->can('view own document') && $user->id === $document->created_by) {
            return true;
        }

        // Doc visible via catégorie partagée (peu importe dept/service)
        if ($document->category_id !== null) {
            $accessibleCategoryIds = app(\App\Services\ProfileCategoryAccessService::class)->accessibleCategoryIdsFor($user);
            if ($accessibleCategoryIds !== null && $accessibleCategoryIds->contains($document->category_id)) {
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

            if ($user->relationLoaded('services') || method_exists($user, 'services')) {
                $visibleServiceIds = $visibleServiceIds->merge($user->services->pluck('id'));
            }

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
        if ($isLocked && ! $this->isDocumentExpired($document)) {
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

    /**
     * Determine whether the user can move a document to a different physical location (box).
     * Allowed for: master, Directrice du SPCR, Chargée de dépôt.
     */
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
        if ($isLocked && ! $this->isDocumentExpired($document)) {
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
