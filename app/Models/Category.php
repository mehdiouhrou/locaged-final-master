<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Service;
use App\Models\SubDepartment;

class Category extends Model
{
    // NOTE: This model relies on Service and SubDepartment for hierarchy-based
    // visibility. Make sure these imports stay in sync with the models.
    protected $table = 'categories';

    protected $fillable = [
        'name',
        'description',
        'department_id',
        'sub_department_id',
        'service_id',
        'sub_department_id',
        'service_id',
        'expiry_value',
        'expiry_unit',
    ];

    protected static function booted()
{
    static::addGlobalScope('service_hierarchy', function ($query) {
        if (! auth()->check()) {
            $query->whereRaw('1 = 0');
            return;
        }
        $user = auth()->user();

        // 1) Accès total : master, Directrice, Assistante de Direction, IT Admin
        if ($user->hasAnyRole([
            'master',
            'Directrice du SPCR',
            'Assistante de Direction',
            'IT Admin',
        ])) {
            return;
        }

        // 2) Chef de Pôle : filtre par department_id
        if ($user->hasRole('Chef de Pôle')) {
            $deptIds = $user->departments->pluck('id');
            if ($deptIds->isNotEmpty()) {
                $query->whereIn('department_id', $deptIds);
            } else {
                $query->whereRaw('1 = 0');
            }
            return;
        }

        // 3) Chef de Département : filtre par sub_department_id
        if ($user->hasRole('Chef de Département')) {
            $subDeptIds = collect();
            if ($user->sub_department_id) {
                $subDeptIds->push($user->sub_department_id);
            }
            if (method_exists($user, 'subDepartments')) {
                $subDeptIds = $subDeptIds->merge($user->subDepartments->pluck('id'));
            }
            $subDeptIds = $subDeptIds->unique()->filter();
            if ($subDeptIds->isNotEmpty()) {
                $query->whereIn('sub_department_id', $subDeptIds);
            } else {
                $query->whereRaw('1 = 0');
            }
            return;
        }

        // 4) user : filtre par service_id
        $serviceIds = collect();
        if ($user->service_id) {
            $serviceIds->push($user->service_id);
        }
        if (method_exists($user, 'services')) {
            $serviceIds = $serviceIds->merge($user->services->pluck('id'));
        }
        $serviceIds = $serviceIds->unique()->filter();
        if ($serviceIds->isNotEmpty()) {
            $query->whereIn('service_id', $serviceIds);
        } else {
            $query->whereRaw('1 = 0');
        }
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

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function subDepartment(): BelongsTo
    {
        return $this->belongsTo(SubDepartment::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
