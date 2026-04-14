<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentMovement extends Model
{
    protected $table = 'document_movements';

    protected $fillable = [
        'document_id',
        'movement_type',
        'moved_from',
        'moved_to',
        'moved_from_box_id',
        'moved_to_box_id',
        'moved_by',
        'borrowed_by_user_id',
        'borrower_name',
        'due_at',
        'returned_at',
        'returned_by_user_id',
        'movement_note',
        'return_note',
        'moved_at',
    ];

    protected $casts = [
        'moved_at' => 'datetime',
        'due_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function movedFrom(): BelongsTo
    {
        return $this->belongsTo(PhysicalLocation::class,'moved_from');
    }

    public function movedTo(): BelongsTo
    {
        return $this->belongsTo(PhysicalLocation::class,'moved_to');
    }

    /**
     * Get the box this document was moved from (new hierarchical structure)
     */
    public function movedFromBox(): BelongsTo
    {
        return $this->belongsTo(Box::class, 'moved_from_box_id');
    }

    /**
     * Get the box this document was moved to (new hierarchical structure)
     */
    public function movedToBox(): BelongsTo
    {
        return $this->belongsTo(Box::class, 'moved_to_box_id');
    }

    public function movedBy(): BelongsTo
    {
        return $this->belongsTo(User::class,'moved_by');
    }

    public function borrowedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'borrowed_by_user_id');
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by_user_id');
    }

    /**
     * Mouvements de type emprunt physique encore actifs (non retournés).
     * Inclut les emprunts avec utilisateur interne ou avec seulement borrower_name (externe).
     */
    public function scopeOpenLoan(Builder $query): Builder
    {
        return $query->where('movement_type', 'retrieval')
            ->whereNull('returned_at')
            ->where(function (Builder $q) {
                $q->whereNotNull('borrowed_by_user_id')
                    ->orWhere(function (Builder $inner) {
                        $inner->whereNotNull('borrower_name')
                            ->where('borrower_name', '!=', '');
                    });
            });
    }

    /**
     * True if this movement is an active physical loan (same rules as {@see scopeOpenLoan}).
     */
    public function isOpenLoan(): bool
    {
        if ($this->movement_type !== 'retrieval' || $this->returned_at !== null) {
            return false;
        }

        if ($this->borrowed_by_user_id !== null) {
            return true;
        }

        $name = trim((string) ($this->borrower_name ?? ''));

        return $name !== '';
    }

    public function __toString()
    {
        return 'From : ' . $this->movedFrom .' To : ' .$this->movedTo .' By : '. $this->movedBy?->full_name;
    }




}
