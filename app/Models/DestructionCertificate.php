<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DestructionCertificate extends Model
{
    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    protected $fillable = [
        'public_id',
        'document_id',
        'document_destruction_request_id',
        'approved_by',
        'manifest',
        'pdf_path',
        'proof_package_path',
        'proof_pdf_sha256',
        'proof_package_sha256',
        'proof_manifest',
        'proof_signature',
        'proof_archive',
        'proof_generated_at',
    ];

    protected $casts = [
        'manifest' => 'array',
        'proof_manifest' => 'array',
        'proof_signature' => 'array',
        'proof_archive' => 'array',
        'proof_generated_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class)->withoutGlobalScopes();
    }

    public function destructionRequest(): BelongsTo
    {
        return $this->belongsTo(DocumentDestructionRequest::class, 'document_destruction_request_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
