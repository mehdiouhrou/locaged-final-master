<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoxFolder extends Model
{
    protected $fillable = [
        'box_id',
        'name',
    ];

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'box_folder_id');
    }

    public function __toString(): string
    {
        $boxNumber = $this->relationLoaded('box') ? $this->box?->box_number : null;

        if ($boxNumber) {
            return $boxNumber . ' - ' . $this->name;
        }

        return (string) $this->name;
    }
}
