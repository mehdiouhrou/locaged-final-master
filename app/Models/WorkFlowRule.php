<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkFlowRule extends Model
{
    protected $table = 'workflow_rules';

    protected $fillable = [
        'department_id',
        'category_id',
        'level',
        'approver_role',
        'approver_user_id',
        'from_status',
        'to_status',
        'is_active',
        'min_amount',
        'max_amount',
    ];

    protected static function booted()
    {
        static::addGlobalScope('department', function ($query) {
            if (auth()->check() && auth()->user()->cannot('view any workflow rule')) {
                if (auth()->user()->can('view department workflow rule')) {
                    $departmentIds = auth()->user()->departments->pluck('id')->toArray();
                    if (!empty($departmentIds)) {
                        $query->whereIn('department_id', $departmentIds);
                    } else {
                        $query->whereRaw('1 = 0'); // Show nothing if no departments assigned
                    }
                } else {
                    // No permission to view any workflow rules
                    $query->whereRaw('1 = 0');
                }
            }
        });
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function approverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    /**
     * Obtenir les utilisateurs éligibles pour cette règle
     */
    public function getApprovers()
    {
        if ($this->approver_user_id) {
            return collect([$this->approverUser]);
        }

        if ($this->approver_role) {
            return User::role($this->approver_role)
                ->whereHas('departments', function ($q) {
                    $q->where('departments.id', $this->department_id);
                })
                ->get();
        }

        return collect();
    }
}
