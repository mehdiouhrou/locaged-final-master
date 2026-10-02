<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role;

class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = [
        'name',
        'description',
        'expiry_value',
        'expiry_unit',
    ];

    protected static function booted()
    {
        static::addGlobalScope('category_access', function ($query) {
            if (! auth()->check()) {
                $query->whereRaw('1 = 0');
                return;
            }

            $user = auth()->user();

            // Bypass : rôles avec accès total
            if ($user->can('view any document')
                || $user->can('create category')
                || $user->can('update category')
                || $user->can('delete category')) {
                return;
            }

            // Catégories directement assignées à l'utilisateur
            $directIds = \DB::table('user_category_access')
                ->where('user_id', $user->id)
                ->pluck('category_id');

            // Catégories parentes des sous-catégories assignées
            $subCatParentIds = \DB::table('user_subcategory_access')
                ->join('subcategories', 'subcategories.id', '=', 'user_subcategory_access.subcategory_id')
                ->where('user_subcategory_access.user_id', $user->id)
                ->pluck('subcategories.category_id');

            $allIds = $directIds->merge($subCatParentIds)->unique()->filter()->values();

            if ($allIds->isEmpty()) {
                $query->whereRaw('1 = 0');
                return;
            }

            $query->whereIn('categories.id', $allIds);
        });
    }

    /**
     * Tous les documents rattachés à cette catégorie (via documents.category_id).
     * N’utilise pas seulement les sous-catégories, sinon les pièces « catégorie seule »
     * (subcategory_id null) n’apparaissent pas dans withCount / listes.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'category_id');
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(Subcategory::class);
    }

    public function profiles(): BelongsToMany
    {
        return $this->belongsToMany(Profile::class, 'profile_category')
            ->withTimestamps();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'category_role')
            ->withTimestamps();
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'category_service')
            ->withTimestamps();
    }

    /**
     * Libellé de conservation pour PV / exports (ex. « 5 ans »).
     */
    public function retentionSummary(): string
    {
        if ($this->expiry_value === null) {
            return '—';
        }

        $u = strtolower((string) $this->expiry_unit);
        $n = (int) $this->expiry_value;
        if (in_array($u, ['year', 'years', 'y'], true)) {
            return $n.' '.($n > 1 ? 'ans' : 'an');
        }
        if (in_array($u, ['month', 'months', 'm'], true)) {
            return $n.' mois';
        }
        if (in_array($u, ['day', 'days', 'd'], true)) {
            return $n.' jour'.($n > 1 ? 's' : '');
        }

        return trim((string) $this->expiry_value).' '.(string) $this->expiry_unit;
    }
}
