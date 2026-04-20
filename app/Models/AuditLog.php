<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'user_name',
        'document_id',
        'version_id',
        'action',
        'ip_address',
        'user_agent',
        'metadata',
        'hash_version',
        'previous_hash',
        'entry_hash',
        'sealed_at',
        'occurred_at',
    ];

    public $timestamps = false;

    protected static function booted()
    {
        static::addGlobalScope('org_visibility', function ($query) {
            if (! auth()->check()) {
                // No audits for guests
                $query->whereRaw('1 = 0');
                return;
            }

            $user = auth()->user();

            if ($user->can('view any document')) {
                return;
            }

            $departmentIds = $user->departments->pluck('id')->filter();

            $canSeeCategoryOrg = $user->can('view organization wide reports')
                || $user->can('view any role');

            // Always restrict audits to logs whose documents the user can see.
            // IMPORTANT: include soft-deleted documents so we can still see who deleted them.
            // Catégories (sans document) : visibles pour super-admin / master uniquement.
            $query->where(function ($q) use ($departmentIds, $canSeeCategoryOrg) {
                $q->whereHas('document', function ($docQuery) use ($departmentIds) {
                    $docQuery->withTrashed();

                    if ($departmentIds->isNotEmpty()) {
                        $docQuery->whereIn('department_id', $departmentIds->all());
                    }
                });

                if ($canSeeCategoryOrg) {
                    $q->orWhere(function ($c) {
                        $c->whereNull('document_id')
                            ->whereIn('action', ['category_created', 'category_updated', 'category_deleted']);
                    });
                }
            });
        });
    }

    public function user(): BelongsTo
    {
        // user_id may be null if the user account was deleted.
        // Historical audit logs are preserved with user_id set to NULL.
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function document(): BelongsTo
    {
        // Bypass global scopes and include soft-deleted documents
        // so audit logs remain visible even for expired or deleted documents
        return $this->belongsTo(Document::class)
            ->withoutGlobalScopes()
            ->withTrashed();
    }

    public function documentVersion(): BelongsTo
    {
        // Include soft-deleted document versions for historical integrity
        return $this->belongsTo(DocumentVersion::class,'version_id','id')->withTrashed();
    }

    protected $casts = [
        'occurred_at' => 'datetime',
        'sealed_at' => 'datetime',
        'metadata' => 'array',
    ];
}
