<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Jobs\ProcessOcrJob;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Services\ProfileCategoryAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Laravel\Scout\Builder as ScoutBuilder;
use Laravel\Scout\Searchable;

class Document extends Model
{
    // SoftDeletes = scope global whereNull(deleted_at) sauf withTrashed()/onlyTrashed() (corbeille / destruction).
    use SoftDeletes;
    use Searchable;

    protected $fillable = [
        'uid',
        'category_id',
        'subcategory_id',
        'department_id',
        'service_id',
        'title',
        'metadata',
        'file_hash',
        'status',
        'entry_type',
        'physical_location_id',
        'box_id',
        'box_folder_id',
        'expire_at',
        'is_expired',
        'created_at',
        'created_by',
    ];

    protected $dates = ['deleted_at'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'expire_at' => 'date',
        'is_expired' => 'boolean',
        'metadata' => 'array',
        // 'status' => DocumentStatus::class
    ];

    protected static function boot()
    {
        parent::boot();

        // Note: department_id is now set explicitly in controllers/forms
        // No automatic assignment to maintain multi-department compatibility

        // Runs on updating event (before an existing record is updated)
        static::updating(function ($document) {

            // Ensure category_id is synced if subcategory_id is present
            if ($document->isDirty('subcategory_id') && $document->subcategory_id) {
                $document->category_id = $document->subcategory->category_id;
            }

            // if status changed
            if ($document->isDirty('status')) {
                $statusFrom = $document->getOriginal('status');

                $currentStatus = $document->status;
                $statusTo = $currentStatus instanceof DocumentStatus
                    ? $currentStatus->value
                    : $currentStatus;

                if (config('ged.enforce_workflow_rules')) {
                    $deptHasRules = WorkFlowRule::withoutGlobalScopes()
                        ->where('department_id', $document->department_id)
                        ->exists();

                    if ($deptHasRules) {
                        $categoryId = $document->category_id;
                        $ruleMatch = WorkFlowRule::withoutGlobalScopes()
                            ->where('department_id', $document->department_id)
                            ->where(function ($q) use ($categoryId) {
                                $q->whereNull('category_id');
                                if ($categoryId !== null) {
                                    $q->orWhere('category_id', $categoryId);
                                }
                            })
                            ->where('from_status', $statusFrom)
                            ->where('to_status', $statusTo)
                            ->exists();

                        if (! $ruleMatch) {
                            throw ValidationException::withMessages([
                                'status' => 'Aucune règle de workflow n’autorise cette transition de statut pour ce département ou cette catégorie.',
                            ]);
                        }
                    }
                }

                DocumentStatusHistory::create([
                    'document_id' => $document->id,
                    'changed_by' => auth()->id(),
                    'from_status' => $statusFrom,
                    'to_status' => $statusTo,
                    'changed_at' => now(),
                ]);
            }
        });

        // Remove document from Typesense and delete OCR jobs when document is soft deleted
        static::deleted(function ($document) {
            try {
                $document->unsearchable();
            } catch (\Throwable $e) {
                \Log::warning('Failed to unsearchable Document on document soft delete', [
                    'document_id' => $document->id,
                    'error' => $e->getMessage(),
                ]);
            }

            foreach ($document->documentVersions as $version) {
                // Delete associated OCR jobs
                try {
                    \App\Models\OcrJob::where('document_version_id', $version->id)->delete();
                } catch (\Throwable $e) {
                    \Log::warning('Failed to delete OCR job on document deletion', [
                        'version_id' => $version->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });
    }

    protected static function booted()
    {
        static::addGlobalScope('department_service', function ($query) {

            if (! auth()->check()) {
                return;
            }
            $user = auth()->user();

            // Always hide documents that are already destroyed or currently
            // queued for destruction (pending / accepted) from the main document
            // and approvals views for ALL roles. Admins can still see them via
            // explicit queries using withoutGlobalScopes().
            $query
                ->whereNotIn('status', ['destroyed'])
                ->whereDoesntHave('destructionsRequests', function ($q) {
                    $q->whereIn('status', ['pending', 'accepted']);
                });

            // Pending visibility (spec) : hors accès total, un document « pending » n'apparaît
            // que pour son auteur ou pour les comptes pouvant approuver / refuser.
            if (! $user->can('view any document')) {
                $pending = DocumentStatus::Pending->value;
                $query->where(function ($w) use ($user, $pending) {
                    $w->where('documents.status', '!=', $pending)
                        ->orWhere('documents.created_by', $user->id);
                    if ($user->can('approve document') || $user->can('decline document')) {
                        $w->orWhere('documents.status', $pending);
                    }
                });
            }

            // Decision 28/07/2026 : un document 'attente_archivage' n'est visible que pour
            // son auteur, ses relecteurs assignés, ou les rôles hiérarchiques (department/
            // subdepartment, filtrés plus bas par leur propre department_id/service_id).
            // Un simple accès catégorie ne suffit pas tant que le document n'est pas 'approved'.
            if (! $user->can('view any document')) {
                $attenteArchivage = DocumentStatus::AttenteArchivage->value;
                $hasHierarchyAccess = $user->can('view department document')
                    || $user->can('view subdepartment scoped documents');

                $query->where(function ($w) use ($user, $attenteArchivage, $hasHierarchyAccess) {
                    $w->where('documents.status', '!=', $attenteArchivage)
                        ->orWhere('documents.created_by', $user->id)
                        ->orWhereHas('reviewers', function ($rq) use ($user) {
                            $rq->where('reviewer_id', $user->id);
                        });

                    if ($hasHierarchyAccess) {
                        $w->orWhere('documents.status', $attenteArchivage);
                    }
                });
            }

            // Bypass department/service scoping for roles that can view any document,
            // but still respect the destruction/expiry / destruction-queue filters.
            if ($user->can('view any document')) {
                return;
            }

            // V2 CDC — visibilité documents alignée sur les profils d’accès aux catégories.
            // Les rôles hiérarchiques gardent leurs périmètres élargis.
            if (config('ged.category_access_driver', 'profile') === 'profile') {
                if ($user->can('view department document')) {
                    $departmentIds = collect();
                    if (method_exists($user, 'departments')) {
                        $departmentIds = $departmentIds->merge($user->departments->pluck('id'));
                    }

                    $subDeptIds = collect();
                    if (method_exists($user, 'subDepartments')) {
                        $subDeptIds = $subDeptIds->merge($user->subDepartments->pluck('id'));
                    }
                    if ($user->sub_department_id) {
                        $subDeptIds->push($user->sub_department_id);
                    }
                    $subDeptIds = $subDeptIds->unique()->filter();
                    if ($subDeptIds->isNotEmpty()) {
                        $departmentIds = $departmentIds->merge(
                            SubDepartment::whereIn('id', $subDeptIds)->pluck('department_id')
                        );
                    }
                    $departmentIds = $departmentIds->unique()->filter()->values();

                    if ($departmentIds->isEmpty()) {
                        $query->whereRaw('1 = 0');
                    } else {
                        $query->whereIn('documents.department_id', $departmentIds->all());
                    }

                    return;
                }

                if ($user->can('view subdepartment scoped documents')) {
                    $userSubDeptIds = collect();
                    if (method_exists($user, 'subDepartments')) {
                        $userSubDeptIds = $userSubDeptIds->merge($user->subDepartments->pluck('id'));
                    }
                    $userSubDeptIds = $userSubDeptIds->unique()->filter();

                    if ($userSubDeptIds->isEmpty()) {
                        $query->whereRaw('1 = 0');

                        return;
                    }

                    $assignedSubDepts = SubDepartment::with('services:id,sub_department_id')
                        ->whereIn('id', $userSubDeptIds)
                        ->get(['id', 'department_id']);

                    $query->where(function ($mainQuery) use ($assignedSubDepts) {
                        foreach ($assignedSubDepts as $subDept) {
                            $validServiceIds = $subDept->services->pluck('id');
                            if ($validServiceIds->isEmpty()) {
                                continue;
                            }

                            $mainQuery->orWhere(function ($q) use ($subDept, $validServiceIds) {
                                $q->where('department_id', $subDept->department_id)
                                    ->whereIn('service_id', $validServiceIds);
                            });
                        }
                    });

                    return;
                }

                $categoryIds = app(ProfileCategoryAccessService::class)->accessibleCategoryIdsFor($user);
                if ($categoryIds === null) {
                    return;
                }
                $ids = $categoryIds->all();
                $pending = DocumentStatus::Pending->value;

                if (empty($ids) && ! $user->can('view own document')) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->where(function ($q) use ($ids, $user, $pending) {
                    if (! empty($ids)) {
                        $q->whereIn('documents.category_id', $ids)
                            ->orWhereHas('subcategory', function ($sq) use ($ids) {
                                $sq->whereIn('subcategories.category_id', $ids);
                            });
                    }

                    if ($user->can('view own document')) {
                        $q->orWhere(function ($own) use ($user, $pending) {
                            $own->where('documents.created_by', $user->id)
                                ->where('documents.status', $pending);
                        });
                    }
                });

                return;
            }

            // =========================================================
            //  STRICT DIVISION CHIEF POLICY (legacy driver)
            // =========================================================
            if ($user->can('view subdepartment scoped documents')) {

                // 1. Get all Sub-Departments assigned to this user (pivot only)
                $userSubDeptIds = collect();
                // Pivot assignment (if exists)
                if (method_exists($user, 'subDepartments')) {
                    $userSubDeptIds = $userSubDeptIds->merge($user->subDepartments->pluck('id'));
                }

                $userSubDeptIds = $userSubDeptIds->unique()->filter();

                // If no sub-departments, show nothing
                if ($userSubDeptIds->isEmpty()) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                // 2. Fetch the SubDepartments with their Services AND their defined Department ID
                // We DO NOT rely on the User's department list. We rely on the SubDepartment's database record.
                $assignedSubDepts = SubDepartment::with('services:id,sub_department_id')
                    ->whereIn('id', $userSubDeptIds)
                    ->get(['id', 'department_id']);

                // 3. Build the Strict Query
                $query->where(function ($mainQuery) use ($assignedSubDepts) {

                    foreach ($assignedSubDepts as $subDept) {

                        // Get services for this specific sub-department
                        $validServiceIds = $subDept->services->pluck('id');

                        if ($validServiceIds->isEmpty()) {
                            continue;
                        }

                        // LOGIC FIX:
                        // We use '$subDept->department_id' (The real owner), NOT the user's department list.
                        // This ensures that even if User 2 is assigned 'Dept 2',
                        // if they are looking at SubDept 1 (which belongs to Dept 1),
                        // the query forces 'department_id = 1'.
                        // Since the document is Dept 1, User 1 sees it.
                        // User 2 (who expects Dept 2 documents) will NOT match this condition.

                        $mainQuery->orWhere(function ($q) use ($subDept, $validServiceIds) {
                            $q->where('department_id', $subDept->department_id)
                                ->whereIn('service_id', $validServiceIds);
                        });
                    }
                });

                return;
            }
            // =========================================================
            //  END DIVISION CHIEF POLICY
            // =========================================================

            // -------------------------------
            // Other roles
            // -------------------------------

            // Resolve visible departments and services from pivots
            $visibleDepartmentIds = collect();
            $visibleServiceIds = collect();

            // Departments directly assigned via pivot
            if (method_exists($user, 'departments')) {
                $visibleDepartmentIds = $visibleDepartmentIds->merge($user->departments->pluck('id'));
            }

            // Sub-departments via pivot -> their departments and services
            $subDeptIds = collect();
            if (method_exists($user, 'subDepartments')) {
                $subDeptIds = $subDeptIds->merge($user->subDepartments->pluck('id'));
            }

            // Also check for primary sub_department_id assignment
            if ($user->sub_department_id) {
                $subDeptIds->push($user->sub_department_id);
            }

            $subDeptIds = $subDeptIds->unique()->filter();

            if ($subDeptIds->isNotEmpty()) {
                // Departments from these sub-departments
                $visibleDepartmentIds = $visibleDepartmentIds->merge(
                    SubDepartment::whereIn('id', $subDeptIds)->pluck('department_id')
                );

                // IMPORTANT: Only include sub-department services for NON-service-level users
                // Service Managers should ONLY see their DIRECTLY assigned services
                if (! $user->can('view service document')) {
                    $visibleServiceIds = $visibleServiceIds->merge(
                        Service::whereIn('sub_department_id', $subDeptIds)->pluck('id')
                    );
                }
            }

            // Services DIRECTLY assigned to the user (THIS IS THE KEY FOR SERVICE MANAGERS)
            // 1. Check primary service_id column
            if ($user->service_id) {
                $visibleServiceIds->push($user->service_id);
            }

            // 2. Check many-to-many pivot table (service_user)
            if (method_exists($user, 'services')) {
                $visibleServiceIds = $visibleServiceIds->merge($user->services->pluck('id'));
            }

            $visibleDepartmentIds = $visibleDepartmentIds->unique()->filter();
            $visibleServiceIds = $visibleServiceIds->unique()->filter();

            // Service-level visibility (Service Manager / Service User)
            if ($user->can('view service document')) {
                if ($visibleServiceIds->isNotEmpty()) {
                    // Show documents from assigned services
                    // If user can also view own documents, include those too
                    if ($user->can('view own document')) {
                        $query->where(function ($q) use ($visibleServiceIds, $user) {
                            $q->whereIn('documents.service_id', $visibleServiceIds->all())
                                ->orWhere('documents.created_by', $user->id);
                        });
                    } else {
                        // Simple filter by service_id only
                        $query->whereIn('documents.service_id', $visibleServiceIds->all());
                    }
                } else {
                    // No services assigned, but if they can view own documents, show those
                    if ($user->can('view own document')) {
                        $query->where('documents.created_by', $user->id);
                    } else {
                        // No visible services assigned and can't view own => no documents
                        $query->whereRaw('1 = 0');
                    }
                }

                return;
            }

            // Department-level visibility (Department Admin etc.)
            if ($user->can('view department document')) {                    // Scope to visible departments (only show documents from assigned departments)
                if ($visibleDepartmentIds->isNotEmpty()) {
                    $query->whereIn('documents.department_id', $visibleDepartmentIds->all());
                } else {
                    $query->whereRaw('1 = 0');
                }

                return;
            }

            // Fallback: only own documents
            if ($user->can('view own document')) {
                $query->where('created_by', $user->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        });
    }

    public function documentVersions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'document_id');
    }

    public function documentMovements(): HasMany
    {
        return $this->hasMany(DocumentMovement::class, 'document_id')
            ->orderByDesc('moved_at')
            ->orderByDesc('id');
    }

    /**
     * Dernier emprunt physique non retourné, indépendamment du nombre de mouvements en historique.
     */
    public function currentOpenLoan(): ?DocumentMovement
    {
        return $this->documentMovements()
            ->openLoan()
            ->with([
                'movedBy:id,full_name',
                'borrowedBy:id,full_name',
                'returnedBy:id,full_name',
                'movedFromBox',
                'movedToBox',
            ])
            ->first();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(DocumentStatusHistory::class, 'document_id')->orderBy('changed_at');
    }

    public function reviewers(): HasMany
    {
        return $this->hasMany(DocumentReviewer::class, 'document_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(DocumentComment::class, 'document_id')->latest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DocumentAttachment::class, 'document_id')->latest();
    }

    public function allReviewersValidated(): bool
    {
        return $this->reviewers()->exists()
            && $this->reviewers()->where('status', '!=', 'validated')->doesntExist();
    }

    public function resetReviewCycle(): void
    {
        $this->reviewers()->update([
            'status' => 'pending',
            'responded_at' => null,
        ]);
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(DocumentVersion::class, 'document_id')->orderByDesc('version_number');
    }

    public function destructionsRequests(): HasMany
    {
        return $this->hasMany(DocumentDestructionRequest::class, 'document_id');
    }

    public function destructionCertificates(): HasMany
    {
        return $this->hasMany(DestructionCertificate::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'document_tags');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function physicalLocation()
    {
        return $this->belongsTo(PhysicalLocation::class, 'physical_location_id', 'id');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }

    /**
     * Get the box that contains this document (new hierarchical structure)
     */
    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class, 'box_id');
    }

    public function boxFolder(): BelongsTo
    {
        return $this->belongsTo(BoxFolder::class, 'box_folder_id');
    }

    /**
     * Documents déposés comme « numériques uniquement » (pas de boîte / stockage physique).
     */
    public function scopeDigitalOnly(Builder $query): Builder
    {
        return $query->whereNull('box_id')->where(function ($q) {
            $q->where('metadata->digital_only', true)
                ->orWhere('metadata->digital_only', 1)
                ->orWhere('metadata->digital_only', '1')
                ->orWhere('metadata->digital_only', 'true');
        });
    }

    public function isDigitalOnly(): bool
    {
        if ($this->box_id) {
            return false;
        }

        return filter_var(data_get($this->metadata, 'digital_only'), FILTER_VALIDATE_BOOLEAN);
    }

    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'document_id')
            ->orderBy('occurred_at', 'desc');
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function logAction(string $action, ?int $versionId = null, array $metadata = [])
    {
        $resolvedVersionId = $versionId ?? $this->latestVersion?->id;

        // If we somehow don't have a version, skip audit/notifications to avoid DB constraint errors.
        if (! $resolvedVersionId) {
            return;
        }

        AuditService::log($action, $this, $resolvedVersionId, $metadata);

        // Only send notifications if the document creator exists
        // Load the relationship if not already loaded
        $creator = $this->createdBy;

        if ($creator) {
            $notificationService = new NotificationService(
                $this->title,
                $creator,
                $this->id,
                $resolvedVersionId
            );
            $notificationService->notifyBasedOnAction($action);

            if (in_array($action, ['expired', 'moved'])) {
                $notificationService->notifyAdmins($action);
            }
        } elseif (in_array($action, ['expired', 'moved'])) {
            // If creator doesn't exist but action requires admin notification,
            // create a minimal notification service just for admin notifications
            // We need to use a valid user, so use the current authenticated user
            if (auth()->check()) {
                $notificationService = new NotificationService(
                    $this->title,
                    auth()->user(),
                    $this->id,
                    $resolvedVersionId
                );
                $notificationService->notifyAdmins($action);
            }
        }

    }

    public function searchableAs(): string
    {
        return 'documents';
    }

    /**
     * Load documents returned by the search engine without the visibility scope;
     * {@see DocumentSearchService::applyPermissionFilter()} enforces access rules.
     */
    public function queryScoutModelsByIds(ScoutBuilder $builder, array $ids)
    {
        $query = static::usesSoftDelete()
            ? $this->withTrashed()
            : $this->newQuery();

        $query->withoutGlobalScope('department_service');

        if ($builder->queryCallback) {
            call_user_func($builder->queryCallback, $query);
        }

        $whereIn = in_array($this->getScoutKeyType(), ['int', 'integer']) ?
            'whereIntegerInRaw' :
            'whereIn';

        return $query->{$whereIn}(
            $this->qualifyColumn($this->getScoutKeyName()),
            $ids
        );
    }

    /**
     * @param  Builder<\App\Models\Document>  $query
     * @return Builder<\App\Models\Document>
     */
    protected static function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->withoutGlobalScope('department_service')
            ->with(['latestVersion', 'category', 'createdBy', 'tags']);
    }

    public function shouldBeSearchable(): bool
    {
        $status = $this->status instanceof DocumentStatus
            ? $this->status->value
            : (string) ($this->status ?? '');

        return $status === DocumentStatus::Approved->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $latest = $this->relationLoaded('latestVersion')
            ? $this->latestVersion
            : $this->latestVersion()->first();

        $status = $this->status instanceof DocumentStatus
            ? $this->status->value
            : (string) ($this->status ?? '');

        $uploadedAt = $latest?->uploaded_at?->getTimestamp()
            ?? $latest?->created_at?->getTimestamp()
            ?? $this->updated_at?->getTimestamp()
            ?? 0;

        return [
            'id' => (string) $this->id,
            'title' => (string) ($this->title ?? ''),
            'content' => (string) ($latest?->ocr_text ?? ''),
            'category_name' => (string) ($this->category?->name ?? ''),
            'status' => $status,
            'created_by_name' => (string) ($this->createdBy?->full_name ?? ''),
            'tags' => $this->tags->pluck('name')->toArray(),
            'uploaded_at' => $uploadedAt,
        ];
    }

    /**
     * Queue an OCR job for the latest version of this document if and only if
     * the document is approved and no active/completed OCR job exists yet.
     */
    public function queueOcrIfNeeded(): void
    {
        // Support both enum-casted and plain string status values.
        $status = $this->status instanceof DocumentStatus
            ? $this->status->value
            : $this->status;

        if ($status !== DocumentStatus::Approved->value) {
            return;
        }

        $latestVersion = $this->latestVersion;
        if (! $latestVersion) {
            return;
        }

        // Avoid creating duplicate OCR jobs for the same version.
        $existingJob = OcrJob::where('document_version_id', $latestVersion->id)
            ->whereIn('status', ['queued', 'processing', 'completed'])
            ->first();

        if ($existingJob) {
            return;
        }

        $ocrJob = OcrJob::create([
            'document_version_id' => $latestVersion->id,
            'status' => 'queued',
            'queued_at' => now(),
        ]);

        ProcessOcrJob::dispatch($ocrJob);
    }
}
