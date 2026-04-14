<?php

namespace App\Models;

use App\Services\ProfileCategoryAccessService;
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

            // Accès liste complète des catégories (hors filtre profils) : rôles gestionnaires.
            if ($user->can('view any document')
                || $user->can('create category')
                || $user->can('update category')
                || $user->can('delete category')) {
                return;
            }

            $ids = app(ProfileCategoryAccessService::class)->accessibleCategoryIdsFor($user);
            if ($ids === null) {
                return;
            }
            if ($ids->isEmpty()) {
                $query->whereRaw('1 = 0');

                return;
            }
            $query->whereIn('categories.id', $ids);
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
}
