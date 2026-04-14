<?php

namespace App\Models;

use App\Services\ProfileCategoryAccessService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
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

    public function documents(): HasManyThrough
    {
        return $this->hasManyThrough(Document::class, Subcategory::class);
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
