<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentApproval extends Model
{
    protected $fillable = [
        'document_id',
        'workflow_rule_id',
        'level',
        'approver_user_id',
        'status',
        'comments',
        'approved_at',
        'declined_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'declined_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function workflowRule(): BelongsTo
    {
        return $this->belongsTo(WorkFlowRule::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }
}
