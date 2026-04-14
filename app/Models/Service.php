<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'name',
        'sub_department_id',
    ];

    public function subDepartment(): BelongsTo
    {
        return $this->belongsTo(SubDepartment::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function usersViaPivot(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'service_user')
            ->withTimestamps();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_service')
            ->withTimestamps();
    }

    public function profiles(): BelongsToMany
    {
        return $this->belongsToMany(Profile::class, 'profile_service')
            ->withTimestamps();
    }
}
