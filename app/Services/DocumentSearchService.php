<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Service;
use App\Models\SubDepartment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DocumentSearchService
{
    /**
     * Search documents using Laravel Scout (Typesense).
     */
    public static function searchDocuments(string $query = '', array $filters = [], int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        // Step 1: Basic Scout search
        $searchResults = self::performScoutSearch($query);

        // Step 2: Apply permission filtering
        $searchResults = self::applyPermissionFilter($searchResults);

        // Step 3: Apply additional filters
        $searchResults = self::applyFilters($searchResults, $filters);

        // Step 4: Paginate Document models
        return self::paginateResults($searchResults, $perPage, $page);
    }

    /**
     * Search documents by category using Scout.
     */
    public static function searchByCategory(int $categoryId, string $query = '', array $filters = [], int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        // Add category filter
        $filters['category_id'] = $categoryId;

        return self::searchDocuments($query, $filters, $perPage, $page);
    }

    /**
     * Search documents using Scout and return latest {@see DocumentVersion} per hit (for version-centric UIs).
     */
    public static function searchVersions(string $query = '', array $filters = [], int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $searchResults = self::performScoutSearch($query);
        $searchResults = self::applyPermissionFilter($searchResults);
        $searchResults = self::applyFilters($searchResults, $filters);

        return self::paginateVersionResults($searchResults, $perPage, $page);
    }

    /**
     * Get statistics for reports using Scout.
     */
    public static function getStatistics(array $filters = []): array
    {
        $searchResults = self::performScoutSearch('*');
        $searchResults = self::applyPermissionFilter($searchResults);
        $searchResults = self::applyFilters($searchResults, $filters);

        return self::generateStatistics($searchResults);
    }

    /**
     * Perform basic Scout search (Typesense driver).
     */
    private static function performScoutSearch(string $query): Collection
    {
        if (strlen($query) < 2 && $query !== '*') {
            return collect();
        }

        try {
            $builder = Document::search($query ?: '*');
            $searchResults = $builder->get();
        } catch (\Throwable $e) {
            Log::warning('Scout search failed, falling back to empty result set', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }

        $searchResults->load([
            'latestVersion',
            'tags',
            'department',
            'category',
            'subcategory.category',
            'physicalLocation',
            'createdBy',
            'favoritedByUsers',
            'auditLogs.user',
            'box.shelf.row.room',
        ]);

        return $searchResults;
    }

    /**
     * Apply permission filtering (same logic as DocumentElasticSearch)
     */
    private static function applyPermissionFilter(Collection $searchResults): Collection
    {
        $user = auth()->user();
        if (! $user) {
            return collect();
        }

        $isSuper = $user->can('view any role')
            || $user->can('view organization wide reports');

        // Precompute strict visibility for sous-départements (ex-Division Chief / Admin de departments)
        $divisionChiefDeptIds = collect();
        $divisionChiefServiceIds = collect();

        if ($user->can('view subdepartment scoped documents')) {
            $divisionChiefDeptIds = ($user->relationLoaded('departments') || method_exists($user, 'departments'))
                ? $user->departments->pluck('id')->filter()
                : collect();

            $userSubDeptIds = collect();
            if ($user->relationLoaded('subDepartments') || method_exists($user, 'subDepartments')) {
                $userSubDeptIds = $userSubDeptIds->merge($user->subDepartments->pluck('id'));
            }
            $userSubDeptIds = $userSubDeptIds->unique()->filter();

            if ($divisionChiefDeptIds->isNotEmpty() && $userSubDeptIds->isNotEmpty()) {
                $allowedSubDeptIds = SubDepartment::whereIn('id', $userSubDeptIds)
                    ->whereIn('department_id', $divisionChiefDeptIds)
                    ->pluck('id');

                if ($allowedSubDeptIds->isNotEmpty()) {
                    $divisionChiefServiceIds = Service::whereIn('sub_department_id', $allowedSubDeptIds)
                        ->pluck('id')
                        ->unique()
                        ->filter();
                }
            }
        }

        return $searchResults->filter(function ($document) use ($user, $divisionChiefDeptIds, $divisionChiefServiceIds, $isSuper) {
            if (! $document) {
                return false;
            }

            // Hide destroyed documents for everyone except Master / Super Admin
            if (! $isSuper && in_array($document->status, ['destroyed'], true)) {
                return false;
            }

            // Super roles can see everything that passes status filter above
            if ($isSuper) {
                return true;
            }

            // If user can view any document, they can see all remaining documents
            if ($user->can('view any document')) {
                return true;
            }

            // Strict rule for sous-département: must match both department AND one of
            // the services under their assigned sub-departments. No broader fallback.
            if ($user->can('view subdepartment scoped documents')) {
                if ($divisionChiefDeptIds->isEmpty() || $divisionChiefServiceIds->isEmpty()) {
                    return false;
                }

                return $divisionChiefDeptIds->contains($document->department_id)
                    && $document->service_id
                    && $divisionChiefServiceIds->contains($document->service_id);
            }

            // If user can view service documents, check if document is within any
            // of their visible services (including via category hierarchy).
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

                if (! $visibleServiceIds->isEmpty()) {
                    $serviceIds = $visibleServiceIds->all();

                    $docServiceId = $document->service_id;

                    if ($docServiceId && in_array($docServiceId, $serviceIds)) {
                        return true;
                    }
                }
            }

            // If user can view department documents, check if document is in their departments
            if ($user->can('view department document')) {
                $departmentIds = $user->departments->pluck('id')->toArray();
                if (! empty($departmentIds) && in_array($document->department_id, $departmentIds)) {
                    return true;
                }
            }

            // If user can view own documents, check if they created the document
            if ($user->can('view own document') && $document->created_by === $user->id) {
                return true;
            }

            // No permission to view this document
            return false;
        });
    }

    /**
     * Apply additional filters
     */
    private static function applyFilters(Collection $searchResults, array $filters): Collection
    {
        return $searchResults->filter(function ($document) use ($filters) {
            // FILTER: Status
            if (! empty($filters['status']) && $filters['status'] !== 'all') {
                if ($document->status !== $filters['status']) {
                    return false;
                }
            }

            // FILTER: Category
            if (! empty($filters['category_id'])) {
                $categoryId = $filters['category_id'];
                $directCategoryId = $document->category_id ?? null;
                $viaSubcategoryId = $document->subcategory?->category_id ?? null;

                if ($directCategoryId != $categoryId && $viaSubcategoryId != $categoryId) {
                    return false;
                }
            }

            // FILTER: Subcategory
            if (! empty($filters['subcategory_id'])) {
                if ($document->subcategory_id != $filters['subcategory_id']) {
                    return false;
                }
            }

            // FILTER: Multiple Subcategories
            if (! empty($filters['subcategory_ids'])) {
                if (! in_array($document->subcategory_id, $filters['subcategory_ids'])) {
                    return false;
                }
            }

            // FILTER: Department
            if (! empty($filters['department_id'])) {
                if ($document->department_id != $filters['department_id']) {
                    return false;
                }
            }

            // FILTER: Multiple Departments (used to scope reports to user's departments)
            if (! empty($filters['department_ids'])) {
                $deptIds = is_array($filters['department_ids']) ? $filters['department_ids'] : [$filters['department_ids']];
                if (! in_array($document->department_id, $deptIds)) {
                    return false;
                }
            }

            // FILTER: Services (used for sub-department filter in reports)
            if (! empty($filters['service_ids'])) {
                $serviceIds = is_array($filters['service_ids']) ? $filters['service_ids'] : [$filters['service_ids']];
                if (! in_array($document->service_id, $serviceIds)) {
                    return false;
                }
            }

            // FILTER: File Type (stored as logical category: pdf, doc, image, excel, video, audio, other)
            if (! empty($filters['file_type'])) {
                $fileType = $document->latestVersion?->file_type;
                if ($fileType !== $filters['file_type']) {
                    return false;
                }
            }

            // FILTER: Date Range
            if (! empty($filters['date_from'])) {
                if ($document->created_at < $filters['date_from']) {
                    return false;
                }
            }

            if (! empty($filters['date_to'])) {
                if ($document->created_at > $filters['date_to']) {
                    return false;
                }
            }

            // FILTER: Author
            if (! empty($filters['author'])) {
                $authorFilter = strtolower($filters['author']);
                $fullName = strtolower($document->createdBy->full_name ?? '');
                $email = strtolower($document->createdBy->email ?? '');
                $metadataAuthor = strtolower($document->metadata['author'] ?? '');

                if (! str_contains($fullName, $authorFilter) &&
                    ! str_contains($email, $authorFilter) &&
                    ! str_contains($metadataAuthor, $authorFilter)) {
                    return false;
                }
            }

            // FILTER: Tags
            if (! empty($filters['tags'])) {
                $filterTags = array_filter(array_map('trim', explode(',', strtolower($filters['tags']))));
                $docTags = $document->tags->pluck('name')->map(fn ($t) => strtolower($t))->toArray();
                if (! collect($filterTags)->every(fn ($tag) => in_array($tag, $docTags))) {
                    return false;
                }
            }

            // FILTER: Favorites Only
            if (! empty($filters['favorites_only'])) {
                $isFavorited = $document->favoritedByUsers->contains(auth()->id());
                if (! $isFavorited) {
                    return false;
                }
            }

            // FILTER: Box (Physical Location)
            if (! empty($filters['box_id'])) {
                $boxIds = is_array($filters['box_id']) ? $filters['box_id'] : [$filters['box_id']];
                if (! in_array($document->box_id, $boxIds)) {
                    return false;
                }
            }

            // FILTER: Document ID
            if (! empty($filters['document_id'])) {
                if ($document->id != $filters['document_id']) {
                    return false;
                }
            }

            // FILTER: Created By
            if (! empty($filters['created_by'])) {
                if ($document->created_by != $filters['created_by']) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Paginate results (Document models)
     */
    private static function paginateResults(Collection $searchResults, int $perPage, int $page): LengthAwarePaginator
    {
        $total = $searchResults->count();
        $offset = ($page - 1) * $perPage;

        $items = $searchResults->slice($offset, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * Paginate results and return {@see DocumentVersion} models (latest version per document).
     */
    private static function paginateVersionResults(Collection $searchResults, int $perPage, int $page): LengthAwarePaginator
    {
        $total = $searchResults->count();
        $offset = ($page - 1) * $perPage;

        $pageDocuments = $searchResults->slice($offset, $perPage)->values();
        $pageDocuments->loadMissing('latestVersion');

        $items = $pageDocuments->map(fn ($document) => $document->latestVersion)->filter()->values();

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * Generate statistics from search results
     */
    private static function generateStatistics(Collection $searchResults): array
    {
        return [
            'total_documents' => $searchResults->count(),
            'by_status' => $searchResults->groupBy('status')->map->count(),
            'by_department' => $searchResults->groupBy('department.name')->map->count(),
            'by_category' => $searchResults->groupBy('category.name')->map->count(),
            'by_file_type' => $searchResults->groupBy(function ($document) {
                $path = $document->latestVersion?->file_path;

                return $path ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : 'unknown';
            })->map->count(),
        ];
    }
}
