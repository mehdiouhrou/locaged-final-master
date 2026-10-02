<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Box extends Model
{
    protected $fillable = [
        'shelf_id',
        'service_id',
        'name',
        'box_number',
        'description',
    ];

    /**
     * Get the shelf that owns this box
     */
    public function shelf(): BelongsTo
    {
        return $this->belongsTo(Shelf::class);
    }

    /**
     * Get the service that owns this box
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the row through shelf
     */
    public function row()
    {
        return $this->shelf()->with('row')->first()->row ?? null;
    }

    /**
     * Get the room through shelf and row
     */
    public function room()
    {
        return $this->shelf()->with('row.room')->first()->row->room ?? null;
    }

    /**
     * Get all documents in this box
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'box_id');
    }

    public function boxFolders(): HasMany
    {
        return $this->hasMany(BoxFolder::class);
    }

    public function loanRequests(): HasMany
    {
        return $this->hasMany(\App\Models\LoanRequest::class, 'box_id');
    }

    /**
     * Get full path representation (Room → Row → Shelf → Box)
     */
    public function __toString(): string
    {
        try {
            $this->loadMissing('shelf.row.room');
            $shelf = $this->shelf;
            $row = $shelf?->row;
            $room = $row?->room;
            if (! $shelf || ! $row || ! $room) {
                return (string) $this->name;
            }

            return $room->name.' → '.
                   $row->name.' → '.
                   $shelf->name.' → '.
                   $this->name;
        } catch (\Throwable) {
            return (string) $this->name;
        }
    }

    /**
     * Get the full path as an array
     */
    public function getFullPath(): array
    {
        $this->loadMissing('shelf.row.room');
        $shelf = $this->shelf;
        $row = $shelf?->row;
        $room = $row?->room;

        return [
            'room' => $room?->name ?? '',
            'row' => $row?->name ?? '',
            'shelf' => $shelf?->name ?? '',
            'box' => $this->name,
        ];
    }

    /**
     * Scope to filter boxes by user's accessible services
     */
    public function scopeForUser($query, $user)
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        $accessibleServiceIds = static::getAccessibleServiceIds($user);

        // If user can see all services or has no service restrictions, show all boxes
        if ($accessibleServiceIds === 'all') {
            return $query;
        }

        // If user has no accessible services, show no boxes
        if ($accessibleServiceIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        // Boxes assigned to an accessible service, or shared boxes (no service)
        return $query->where(function ($q) use ($accessibleServiceIds) {
            $q->whereIn('service_id', $accessibleServiceIds)
                ->orWhereNull('service_id');
        });
    }

    /**
     * Restrict a boxes query to what the user may use (and optional service filter for uploads).
     */
    public static function applyPhysicalAccessFilter($query, $user, mixed $selectedServiceId = null): void
    {
        $selectedServiceId = $selectedServiceId !== null && $selectedServiceId !== ''
            ? (int) $selectedServiceId
            : null;

        // Les rôles bypass voient tout — même si un service_id est sélectionné
        if ($user && static::getAccessibleServiceIds($user) === 'all') {
            return;
        }

        if ($selectedServiceId) {
            $query->where('service_id', $selectedServiceId);

            return;
        }

        $accessibleServiceIds = static::getAccessibleServiceIds($user);

        if ($accessibleServiceIds === 'all') {
            return;
        }

        if ($accessibleServiceIds->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function ($q) use ($accessibleServiceIds) {
            $q->whereIn('service_id', $accessibleServiceIds)
                ->orWhereNull('service_id');
        });
    }

    /**
     * Get service IDs accessible to a user based on their role and assignments
     *
     * @return \Illuminate\Support\Collection|string Returns 'all' for admins, or Collection of service IDs
     */
    public static function getAccessibleServiceIds($user)
    {
        if (! $user) {
            return collect();
        }

        if ($user->can('view any role') || $user->can('view organization wide reports') || $user->can('view any box')) {
            return 'all';
        }

        $serviceIds = collect();

        // Pôle : tous les services des sous-départements de leurs départements
        if ($user->can('view any department')) {
            $departmentIds = $user->departments->pluck('id');
            if ($departmentIds->isNotEmpty()) {
                $subDeptIds = \App\Models\SubDepartment::whereIn('department_id', $departmentIds)->pluck('id');
                if ($subDeptIds->isNotEmpty()) {
                    $serviceIds = $serviceIds->merge(
                        \App\Models\Service::whereIn('sub_department_id', $subDeptIds)->pluck('id')
                    );
                }
            }
        }

        $isServiceLevelUser = $user->can('view service document')
            || $user->can('view subdepartment scoped documents');

        if ($isServiceLevelUser) {
            // Direct service assignment via service_id column
            if ($user->service_id) {
                $serviceIds->push($user->service_id);
            }

            // Many-to-many service assignments via pivot table
            if ($user->relationLoaded('services') || method_exists($user, 'services')) {
                $serviceIds = $serviceIds->merge($user->services->pluck('id'));
            }

            if ($user->can('view subdepartment scoped documents')) {
                $subDeptIds = collect();

                if ($user->sub_department_id) {
                    $subDeptIds->push($user->sub_department_id);
                }

                if ($user->relationLoaded('subDepartments') || method_exists($user, 'subDepartments')) {
                    $subDeptIds = $subDeptIds->merge($user->subDepartments->pluck('id'));
                }

                if ($subDeptIds->isNotEmpty()) {
                    $serviceIds = $serviceIds->merge(
                        \App\Models\Service::whereIn('sub_department_id', $subDeptIds)->pluck('id')
                    );
                }
            }
        }

        return $serviceIds->unique()->filter();
    }
}
