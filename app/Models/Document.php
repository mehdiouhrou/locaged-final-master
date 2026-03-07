<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Jobs\ProcessOcrJob;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Model;
use App\Models\Service;
use App\Models\SubDepartment;
use App\Models\Department;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uid',
        'category_id',
        'subcategory_id',
        'department_id',
        'service_id',
        'title',
        'metadata',
        'status',
        'physical_location_id',
        'box_id',
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
    ];

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($document) {
            if ($document->isDirty('subcategory_id') && $document->subcategory_id) {
                $document->category_id = $document->subcategory->category_id;
            }

            if ($document->isDirty('status')) {
                $statusFrom = $document->getOriginal('status');
                $currentStatus = $document->status;
                $statusTo = $currentStatus instanceof DocumentStatus
                    ? $currentStatus->value
                    : $currentStatus;

                DocumentStatusHistory::create([
                    'document_id' => $document->id,
                    'changed_by' => auth()->id(),
                    'from_status' => $statusFrom,
                    'to_status' => $statusTo,
                    'changed_at' => now()
                ]);
            }
        });

        static::deleted(function ($document) {
            foreach ($document->documentVersions as $version) {
                try {
                    $version->unsearchable();
                } catch (\Throwable $e) {
                    \Log::warning('Failed to unsearchable DocumentVersion on document soft delete', [
                        'version_id' => $version->id,
                        'error' => $e->getMessage(),
                    ]);
                }

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

        static::updated(function ($document) {
            if ($document->isDirty('title')) {
                foreach ($document->documentVersions as $version) {
                    if ($version->shouldBeSearchable()) {
                        $version->searchable();
                    }
                }
            }
        });
    }

    protected static function booted()
    {
        static::addGlobalScope('department_service', function ($query) {

            if (!auth()->check()) {
                return;
            }
            $user = auth()->user();

            $query
                ->whereNotIn('status', ['destroyed'])
                ->whereDoesntHave('destructionsRequests', function ($q) {
                    $q->whereIn('status', ['pending', 'accepted']);
                });

            if ($user->can('view any document')) {
                return;
            }

            // =========================================================
            //  STRICT DIVISION CHIEF POLICY
            // =========================================================
            if ($user->hasAnyRole(['Division Chief', 'Chef de Département'])) {
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
            // =========================================================
            //  END DIVISION CHIEF POLICY
            // =========================================================

            $visibleDepartmentIds = collect();
            $visibleServiceIds    = collect();

            if (method_exists($user, 'departments')) {
                $visibleDepartmentIds = $visibleDepartmentIds->merge($user->departments->pluck('id'));
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
                $visibleDepartmentIds = $visibleDepartmentIds->merge(
                    SubDepartment::whereIn('id', $subDeptIds)->pluck('department_id')
                );
                if (! $user->can('view service document')) {
                    $visibleServiceIds = $visibleServiceIds->merge(
                        Service::whereIn('sub_department_id', $subDeptIds)->pluck('id')
                    );
                }
            }

            if ($user->service_id) {
                $visibleServiceIds->push($user->service_id);
            }
            if (method_exists($user, 'services')) {
                $visibleServiceIds = $visibleServiceIds->merge($user->services->pluck('id'));
            }

            $visibleDepartmentIds = $visibleDepartmentIds->unique()->filter();
            $visibleServiceIds    = $visibleServiceIds->unique()->filter();

            // Service-level visibility
            if ($user->can('view service document')) {
                if ($visibleServiceIds->isNotEmpty()) {
                    // Catégories partagées avec les services de l'utilisateur
                    $sharedCategoryIds = \DB::table('category_service')
                        ->whereIn('service_id', $visibleServiceIds->all())
                        ->pluck('category_id');

                    if ($user->can('view own document')) {
                        $query->where(function($q) use ($visibleServiceIds, $sharedCategoryIds, $user) {
                            $q->whereIn('documents.service_id', $visibleServiceIds->all())
                              ->orWhereIn('documents.category_id', $sharedCategoryIds)
                              ->orWhere('documents.created_by', $user->id);
                        });
                    } else {
                        $query->where(function($q) use ($visibleServiceIds, $sharedCategoryIds) {
                            $q->whereIn('documents.service_id', $visibleServiceIds->all())
                              ->orWhereIn('documents.category_id', $sharedCategoryIds);
                        });
                    }
                } else {
                    if ($user->can('view own document')) {
                        $query->where('documents.created_by', $user->id);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                }
                return;
            }

            // Department-level visibility
            if ($user->can('view department document')) {
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

    public function latestVersion(): HasOne
    {
        return $this->hasOne(DocumentVersion::class, 'document_id')->orderByDesc('version_number');
    }

    public function destructionsRequests(): HasMany
    {
        return $this->hasMany(DocumentDestructionRequest::class, 'document_id');
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

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class, 'box_id');
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

    public function logAction(string $action, ?int $versionId = null)
    {
        $resolvedVersionId = $versionId ?? $this->latestVersion?->id;

        if (! $resolvedVersionId) {
            return;
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->check() ? auth()->user()->full_name : null,
            'document_id' => $this->id,
            'version_id' => $resolvedVersionId,
            'action' => $action,
            'ip_address' => request()->ip(),
            'occurred_at' => now(),
        ]);

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

    public function queueOcrIfNeeded(): void
    {
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
