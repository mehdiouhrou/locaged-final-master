<?php

namespace App\Models;

use App\Services\ProfileCategoryAccessService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class Profile extends Model
{
    protected $fillable = [
        'name',
        'description',
        'created_by',
    ];

    /**
     * @return Collection<int, int>|null IDs catégorie accessibles pour l’utilisateur (null = pas de filtre / tout).
     */
    public static function getAccessibleCategoryIds(User $user): ?Collection
    {
        return app(ProfileCategoryAccessService::class)->accessibleCategoryIdsFor($user);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'profile_category')
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'profile_user')
            ->withTimestamps();
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'profile_service')
            ->withTimestamps();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'profile_role')
            ->withTimestamps();
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'profile_department')
            ->withTimestamps();
    }
}
