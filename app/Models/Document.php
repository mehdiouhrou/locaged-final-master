<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Laravel\Scout\Searchable;

class Document extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $fillable = [
        'title',
        'description',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'category_id',
        'sub_category_id',
        'service_id',
        'user_id',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'expiry_date',
        'archived_at',
        'room',
        'shelf_row',
        'shelf',
        'box',
        'metadata',
        'tags',
        'reference_number',
        'document_date',
        'original_filename',
        'disk',
        'thumbnail_path',
        'box_id',
        'box_folder_id',
        'uid',
        'folder_id',
        'created_by',
        'department_id',
        'sub_department_id',
        'subcategory_id',
        'expire_at',
        'physical_location_id',
    ];

    protected $casts = [
        'metadata'    => 'array',
        'tags'        => 'array',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'expiry_date' => 'date',
        'expire_at'   => 'date',
        'archived_at' => 'datetime',
    ];

    // ──────────────────────────────────────────────────────────────
    // Global Scope — visibilité par service/département
    // ──────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (self $document) {
            if (empty($document->uid)) {
                $document->uid = (string) \Illuminate\Support\Str::uuid();
            }
            if (empty($document->created_by)) {
                $document->created_by = \Illuminate\Support\Facades\Auth::id();
            }
        });

        static::addGlobalScope('department_service', function (Builder $query) {
            $user = Auth::user();

            if (! $user) {
                $query->whereRaw('0 = 1');
                return;
            }

            // Rôles "view any document" → voient tout sans filtrage
            if ($user->can('view any document')) {
                return;
            }

            // Récupérer le service de l'utilisateur et sa hiérarchie
            $userService   = $user->service;
            $userServiceId = $userService?->id;
            $userSubDeptId = $userService?->sub_department_id;
            $userDeptId    = $userService?->subDepartment?->department_id;

            // Construire la liste des services dans le périmètre de l'utilisateur
            $visibleServiceIds = collect();

            if ($userServiceId) {
                $visibleServiceIds->push($userServiceId);
            }

            if ($user->can('view subdepartment scoped documents') && $userSubDeptId) {
                $ids = Service::where('sub_department_id', $userSubDeptId)->pluck('id');
                $visibleServiceIds = $visibleServiceIds->merge($ids);
            }

            if ($user->can('view department document') && $userDeptId) {
                $ids = Service::whereHas('subDepartment', function ($q) use ($userDeptId) {
                    $q->where('department_id', $userDeptId);
                })->pluck('id');
                $visibleServiceIds = $visibleServiceIds->merge($ids);
            }

            $visibleServiceIds = $visibleServiceIds->unique()->values();

            // Règle :
            // - Pas de restrictions (aucune ligne pivot) → visible seulement "view any" → exclu ici
            // - Avec restrictions → visible si le service de l'user est dans la pivot
            $query->whereHas('visibleToServices', function (Builder $inner) use ($visibleServiceIds) {
                $inner->whereIn('services.id', $visibleServiceIds->all());
            });
        });
    }

    // ──────────────────────────────────────────────────────────────
    // Relations
    // ──────────────────────────────────────────────────────────────

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Services autorisés à voir ce document (table pivot document_service_visibility).
     * Vide = aucune restriction = visible uniquement aux "view any document".
     */
    public function visibleToServices()
    {
        return $this->belongsToMany(
            Service::class,
            'document_service_visibility',
            'document_id',
            'service_id'
        )->withTimestamps();
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(\App\Models\Subcategory::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function approvals()
    {
        return $this->hasMany(DocumentApproval::class);
    }

    public function comments()
    {
        return $this->hasMany(DocumentComment::class);
    }

    public function activities()
    {
        return $this->hasMany(DocumentActivity::class);
    }

    public function documentVersions()
    {
        return $this->hasMany(\App\Models\DocumentVersion::class, 'document_id');
    }

    public function versions()
    {
        return $this->hasMany(DocumentVersion::class);
    }

    // ──────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────

    public function syncVisibleServices(array $serviceIds): void
    {
        $this->visibleToServices()->sync($serviceIds);
    }

    public function hasVisibilityRestrictions(): bool
    {
        return $this->visibleToServices()->exists();
    }

    // ──────────────────────────────────────────────────────────────
    // Scopes utilitaires
    // ──────────────────────────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expiry_date')
                     ->where('expiry_date', '<', now());
    }

    public function scopeDigitalOnly(Builder $query): Builder
    {
        return $query->whereNull('box_id');
    }

    public function scopePhysicalOnly(Builder $query): Builder
    {
        return $query->whereNotNull('box_id');
    }

    // ──────────────────────────────────────────────────────────────
    // Relations manquantes
    // ──────────────────────────────────────────────────────────────

    public function reviewers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\DocumentReviewer::class, 'document_id');
    }

    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function tags(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Tag::class, 'document_tags', 'document_id', 'tag_id');
    }

    public function favoritedByUsers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Models\User::class, 'favorites', 'document_id', 'user_id');
    }

    public function latestVersion(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\DocumentVersion::class, 'document_id')
                    ->orderByDesc('version_number');
    }

    /**
     * Raccourci pour journaliser une action sur ce document via AuditService.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function department(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Department::class, 'department_id');
    }

    public function subDepartment(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\SubDepartment::class, 'sub_department_id');
    }

    public function folder(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Folder::class, 'folder_id');
    }

    public function currentOpenLoan(): ?\App\Models\LoanRequest
    {
        return \App\Models\LoanRequest::where('document_id', $this->id)
            ->whereIn('status', ['requested', 'approved', 'picked_up'])
            ->latest()
            ->first();
    }

    public function isDigitalOnly(): bool
    {
        return is_null($this->box_id);
    }

    public function isPhysicalOnly(): bool
    {
        return !is_null($this->box_id);
    }

    public function auditLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\AuditLog::class, 'document_id');
    }

    public function box(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Box::class, 'box_id');
    }

    public function boxFolder(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\BoxFolder::class, 'box_folder_id');
    }

    public function logAction(string $action, ?int $versionId = null, array $metadata = []): void
    {
        \App\Services\AuditService::log($action, $this, $versionId, $metadata);
    }
}
