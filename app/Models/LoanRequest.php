<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanRequest extends Model
{
    protected $table = 'loan_requests';

    protected $fillable = [
        'document_id',
        'box_id',
        'requested_by',
        'reason',
        'requested_duration_days',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'movement_id',
        'due_at',
        'picked_up_at',
        'returned_at',
        'return_note',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'due_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class)->withoutGlobalScopes();
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(DocumentMovement::class, 'movement_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'requested');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['approved', 'picked_up']);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', 'picked_up')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now());
    }

    public function isForBox(): bool
    {
        return ! is_null($this->box_id);
    }

    public function isForDocument(): bool
    {
        return ! is_null($this->document_id);
    }

    /**
     * Resolve the department_id relevant to this loan request, whether it targets
     * a document (document->department_id) or a box (box->service->subDepartment->department_id).
     * Returns null if it cannot be determined (e.g. box with no service_id).
     */
    public function resolveDepartmentId(): ?int
    {
        if ($this->isForDocument()) {
            return $this->document?->department_id;
        }

        if ($this->isForBox()) {
            $box = $this->box()->withoutGlobalScopes()->first();

            return $box?->service?->subDepartment?->department_id;
        }

        return null;
    }

    public function __toString()
    {
        $target = $this->isForBox() ? ('Box #' . $this->box_id) : ('Document #' . $this->document_id);

        return $target . ' requested by ' . $this->requestedBy?->full_name . ' [' . $this->status . ']';
    }
}
