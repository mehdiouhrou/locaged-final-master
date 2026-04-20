<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityLogsTable extends Component
{
    use WithPagination;

    public $search = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $userId = '';
    public $departmentId = '';
    public $actionType = '';
    public $perPage = 25;

    public $logType = 'documents'; // 'documents' or 'authentication'

    protected $queryString = [
        'search' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'userId' => ['except' => ''],
        'departmentId' => ['except' => ''],
        'actionType' => ['except' => ''],
        'perPage' => ['except' => 25],
        'logType' => ['except' => 'documents'],
    ];

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    public function updated($field)
    {
        if (in_array($field, ['search', 'dateFrom', 'dateTo', 'userId', 'departmentId', 'actionType', 'perPage', 'logType'])) {
            $this->resetPage();
        }
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->userId = '';
        $this->departmentId = '';
        $this->actionType = '';
        $this->perPage = 25;
        $this->logType = 'documents';
        $this->resetPage();
    }

    public function setLogType($type)
    {
        $this->logType = $type;
        $this->resetPage();
    }

    public function export()
    {
        Gate::authorize('viewAny', User::class);
        
        $query = $this->buildQuery();
        $logs = $query->get();

        $filename = 'activity_logs_' . $this->logType . '_' . now()->format('Y-m-d_His') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ActivityLogsExport($logs, $this->logType),
            $filename
        );
    }

    private function buildQuery()
    {
        if ($this->logType === 'authentication') {
            return $this->buildAuthenticationQuery();
        }
        return $this->buildDocumentQuery();
    }

    private function buildAuthenticationQuery()
    {
        $current = auth()->user();

        $hasOrgReport = $current && $current->can('view organization wide reports');
        $isMaster = $current && $current->can('view any role');
        $isSuperAdminNotMaster = $hasOrgReport && ! $isMaster;

        $isPoleOrDeptAuditor = $current && $current->can('filter audit logs by assigned departments');
        $isSubdeptAuditor = $current && $current->can('filter audit logs by assigned subdepartments');
        $isServiceAuditor = $current && $current->can('filter audit logs by assigned services');

        return \App\Models\AuthenticationLog::with(['user', 'user.roles'])
            // Super Admin: hide logs from master users
            ->when($isSuperAdminNotMaster, function($q) {
                $q->whereDoesntHave('user.roles', function($r) {
                    $r->whereRaw('LOWER(name) = ?', ['master']);
                });
            })
            // Pôle / administrateur département : périmètre départements assignés
            ->when($isPoleOrDeptAuditor && ! $hasOrgReport, function($q) use ($current) {
                $deptIds = $current->departments?->pluck('id') ?? collect();
                $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);
                
                $q->where(function($q1) use ($deptIds, $allowedRoleNames) {
                    $q1->whereHas('user', function($q2) use ($deptIds, $allowedRoleNames) {
                        // User must be in one of the admin's departments
                        $q2->whereHas('departments', function($q3) use ($deptIds) {
                            $q3->whereIn('departments.id', $deptIds);
                        })
                        // AND user must have a role below the admin's rank
                        ->whereHas('roles', function($q3) use ($allowedRoleNames) {
                            $q3->whereIn('name', $allowedRoleNames);
                        });
                    })->orWhereNull('user_id'); // Allow logs from deleted users
                });
            })
            // Sous-départements : uniquement si pas déjà couvert par le périmètre « pôle »
            ->when($isSubdeptAuditor && ! $isPoleOrDeptAuditor && ! $hasOrgReport, function($q) use ($current) {
                $subDeptIds = $current->subDepartments?->pluck('id') ?? collect();
                $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);
                
                $q->where(function($q1) use ($subDeptIds, $allowedRoleNames) {
                    $q1->whereHas('user', function($q2) use ($subDeptIds, $allowedRoleNames) {
                        // User must be in one of the admin's sub-departments
                        $q2->whereHas('subDepartments', function($q3) use ($subDeptIds) {
                            $q3->whereIn('sub_departments.id', $subDeptIds);
                        })
                        // AND user must have a role below the admin's rank
                        ->whereHas('roles', function($q3) use ($allowedRoleNames) {
                            $q3->whereIn('name', $allowedRoleNames);
                        });
                    })->orWhereNull('user_id'); // Allow logs from deleted users
                });
            })
            // Service / cellule
            ->when($isServiceAuditor && ! $isPoleOrDeptAuditor && ! $isSubdeptAuditor && ! $hasOrgReport, function($q) use ($current) {
                $serviceIds = $this->getAccessibleServiceIds($current);
                $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);

                $q->where(function($q1) use ($serviceIds, $allowedRoleNames, $current) {
                    $q1->whereHas('user', function($q2) use ($serviceIds, $allowedRoleNames, $current) {
                         $q2->where(function($query) use ($serviceIds, $allowedRoleNames, $current) {
                            // Case A: Subordinate User in Manager's Service
                            $query->where(function($subQ) use ($serviceIds, $allowedRoleNames) {
                                 $subQ->where(function($sQ) use ($serviceIds) {
                                         // Check Service Assignment (Direct or Pivot)
                                         $sQ->whereIn('users.service_id', $serviceIds)
                                            ->orWhereHas('services', function($pivot) use ($serviceIds) {
                                                $pivot->whereIn('services.id', $serviceIds);
                                            });
                                 })
                                 // Check Rank (Strictly Lower)
                                 ->whereHas('roles', function($r) use ($allowedRoleNames) {
                                     $r->whereIn('name', $allowedRoleNames);
                                 });
                            })
                            // Case B: The Service Manager Themselves
                            ->orWhere('users.id', $current->id);
                         });
                    })->orWhereNull('user_id'); // Allow logs from deleted users
                });
            })
            ->when($this->dateFrom, function($q) {
                $q->whereDate('occurred_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function($q) {
                $q->whereDate('occurred_at', '<=', $this->dateTo);
            })
            ->when($this->userId, function($q) {
                $q->where('user_id', $this->userId);
            })
            ->when($this->actionType, function($q) {
                $q->where('type', $this->actionType);
            })
            ->when($this->search, function($q) {
                $q->where(function($q2) {
                    $q2->where('email', 'like', '%' . $this->search . '%')
                       ->orWhere('ip_address', 'like', '%' . $this->search . '%')
                       ->orWhere('user_name', 'like', '%' . $this->search . '%')
                       ->orWhereHas('user', function($q3) {
                           $q3->where('full_name', 'like', '%' . $this->search . '%');
                       });
                });
            })
            ->latest('occurred_at');
    }

    private function getAccessibleServiceIds(User $user)
    {
        // Matches the fixed logic in HomeController and Document model
        $serviceIds = collect();

        // 1. Direct service assignment
        if ($user->service_id) {
            $serviceIds->push($user->service_id);
        }

        // 2. Many-to-many service assignments via pivot
        if ($user->relationLoaded('services') || method_exists($user, 'services')) {
            $serviceIds = $serviceIds->merge($user->services->pluck('id'));
        }

        // For non-service-level users (like Dept Admins), we would include sub-department services
        // But here we are specifically targeting Service Managers who only see DIRECT services.
        
        return $serviceIds->unique()->filter();
    }

    private function buildDocumentQuery()
    {
        $current = auth()->user();

        $hasOrgReport = $current && $current->can('view organization wide reports');
        $isMaster = $current && $current->can('view any role');
        $isSuperAdminNotMaster = $hasOrgReport && ! $isMaster;

        $isPoleOrDeptAuditor = $current && $current->can('filter audit logs by assigned departments');
        $isSubdeptAuditor = $current && $current->can('filter audit logs by assigned subdepartments');
        $isServiceAuditor = $current && $current->can('filter audit logs by assigned services');

        $query = AuditLog::with([
                'user.departments', 
                'user.roles',
                'document' => function($q) {
                    // Include soft-deleted documents and bypass global scopes
                    // so audit logs show document names for all users
                    $q->withoutGlobalScopes()->withTrashed();
                },
                'document.department', 
                'documentVersion'
            ])
            // Exclude OCR view activity from logs
            ->where('action', '!=', 'viewed_ocr')
            // Super Admin: hide logs from master users
            ->when($isSuperAdminNotMaster, function($q) {
                $q->whereDoesntHave('user.roles', function($r) {
                    $r->whereRaw('LOWER(name) = ?', ['master']);
                });
            });

        // Pôle / administrateur département
        $query->when($isPoleOrDeptAuditor && ! $hasOrgReport, function($q) use ($current) {
            $deptIds = $current->departments?->pluck('id') ?? collect();
            $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);
            
            if ($deptIds->isNotEmpty()) {
                $q->where(function($q2) use ($deptIds) {
                    // Document belongs to one of the admin's departments
                    $q2->whereHas('document', function($q3) use ($deptIds) {
                        $q3->withTrashed()->whereIn('documents.department_id', $deptIds);
                    });
                })
                // AND user must be subordinate (or user is deleted)
                ->where(function($userQ) use ($allowedRoleNames) {
                    $userQ->whereHas('user.roles', function($q2) use ($allowedRoleNames) {
                        $q2->whereIn('name', $allowedRoleNames);
                    })->orWhereNull('user_id');
                });
            } else {
                $q->whereRaw('1 = 0');
            }
        });

        // Sous-départements
        $query->when($isSubdeptAuditor && ! $isPoleOrDeptAuditor && ! $hasOrgReport, function($q) use ($current) {
            $subDeptIds = $current->subDepartments?->pluck('id') ?? collect();
            $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);
            
            if ($subDeptIds->isNotEmpty()) {
                // Get all service IDs under these sub-departments
                $serviceIds = \App\Models\Service::whereIn('sub_department_id', $subDeptIds)->pluck('id');
                
                $q->where(function($q2) use ($serviceIds) {
                    // Document belongs to one of the services under the admin's sub-departments
                    $q2->whereHas('document', function($q3) use ($serviceIds) {
                        $q3->withTrashed()->whereIn('documents.service_id', $serviceIds);
                    });
                })
                // AND user must be in one of these sub-departments and subordinate (or user is deleted)
                ->where(function($userQ) use ($subDeptIds, $allowedRoleNames) {
                    $userQ->whereHas('user', function($q2) use ($subDeptIds, $allowedRoleNames) {
                        $q2->whereHas('subDepartments', function($q3) use ($subDeptIds) {
                            $q3->whereIn('sub_departments.id', $subDeptIds);
                        })
                        ->whereHas('roles', function($q3) use ($allowedRoleNames) {
                            $q3->whereIn('name', $allowedRoleNames);
                        });
                    })->orWhereNull('user_id');
                });
            } else {
                $q->whereRaw('1 = 0');
            }
        });

        // Service / cellule
        $query->when($isServiceAuditor && ! $isPoleOrDeptAuditor && ! $isSubdeptAuditor && ! $hasOrgReport, function($q) use ($current) {
            $serviceIds = $this->getAccessibleServiceIds($current);
            $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);

            if ($serviceIds->isNotEmpty()) {
                // Constraint 1: Document MUST be in one of the manager's services
                $q->whereHas('document', function($d) use ($serviceIds) {
                    $d->withTrashed()->whereIn('documents.service_id', $serviceIds);
                })
                // Constraint 2: Actor MUST be (Subordinate OR Myself OR deleted user)
                ->where(function($u) use ($allowedRoleNames, $current) {
                     $u->where('user_id', $current->id)
                       ->orWhereHas('user.roles', function($r) use ($allowedRoleNames) {
                           $r->whereIn('name', $allowedRoleNames);
                       })
                       ->orWhereNull('user_id');
                });
            } else {
                 // No services assigned -> See specific 'own' actions? 
                 // User said "also related to his service". If no service, then satisfy nothing usually.
                 // But typically if I have NO service, strictly speaking "related to his service" is Empty Set.
                 // However, for safety, if I am truly unassigned, I probably shouldn't see doc logs except maybe entirely private ones?
                 // Given the instructions "No other case should be satisfied", 1=0 is safest if no service.
                 $q->whereRaw('1 = 0');
            }
        });

        return $query->when($this->dateFrom, function($q) {
                $q->whereDate('occurred_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function($q) {
                $q->whereDate('occurred_at', '<=', $this->dateTo);
            })
            ->when($this->userId, function($q) {
                $q->where('user_id', $this->userId);
            })
            ->when($this->departmentId, function($q) {
                $q->whereHas('document', function($q2) {
                    $q2->where('department_id', $this->departmentId);
                });
            })
            ->when($this->actionType, function($q) {
                $q->where('action', $this->actionType);
            })
            ->when($this->search, function($q) {
                $term = '%'.$this->search.'%';
                $q->where(function($q2) use ($term) {
                    $q2->whereHas('user', function($q3) use ($term) {
                        $q3->where('full_name', 'like', $term)
                           ->orWhere('email', 'like', $term);
                    })
                    ->orWhere('user_name', 'like', $term)
                    ->orWhereHas('document', function($q3) use ($term) {
                        $q3->where('title', 'like', $term);
                    })
                    ->orWhere('action', 'like', $term)
                    ->orWhere(function ($q3) use ($term) {
                        $q3->whereNull('document_id')
                            ->whereIn('action', ['category_created', 'category_updated', 'category_deleted'])
                            ->where('metadata', 'like', $term);
                    });
                });
            })
            ->latest('occurred_at');
    }

    private function getResult($log): string
    {
        // Determine result based on action type
        $successActions = ['created', 'approved', 'updated', 'downloaded', 'viewed', 'archived', 'renamed', 'locked', 'unlocked', 'moved', 'viewed_ocr', 'category_created', 'category_updated'];
        $failureActions = ['declined', 'failed_access'];
        $deleteActions = ['permanently_deleted', 'destroyed', 'category_deleted'];
        
        if (in_array($log->action, $successActions)) {
            return 'Success';
        } elseif (in_array($log->action, $failureActions)) {
            return 'Failed';
        } elseif (in_array($log->action, $deleteActions)) {
            return 'Deleted';
        }
        
        return 'Completed';
    }

    public function render()
    {
        $query = $this->buildQuery();
        $logs = $query->paginate($this->perPage);

        // Get statistics for cards (respect same department/service scoping and role filtering)
        $current = auth()->user();
        $deptIds = $current && $current->departments ? $current->departments->pluck('id') : collect();
        $isSuper = $current && ($current->can('view any role') || $current->can('view organization wide reports'));

        $docStatsDeptScope = $current && (
            $current->can('filter audit logs by assigned departments')
            || $current->can('filter audit logs by assigned subdepartments')
        );

        $isServiceAuditor = $current && $current->can('filter audit logs by assigned services');

        $statsBase = AuditLog::query()
            ->where('action', '!=', 'viewed_ocr')
            // Pôle / sous-département : stats par département
            ->when($docStatsDeptScope && $deptIds->isNotEmpty() && ! $isSuper, function($q) use ($deptIds, $current) {
                $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);
                
                $q->whereHas('document', function($q2) use ($deptIds) {
                    $q2->withTrashed()->whereIn('department_id', $deptIds);
                })
                ->where(function($userQ) use ($allowedRoleNames) {
                    $userQ->whereHas('user.roles', function($q2) use ($allowedRoleNames) {
                        $q2->whereIn('name', $allowedRoleNames);
                    })->orWhereNull('user_id');
                });
            })
            ->when($isServiceAuditor && ! $docStatsDeptScope && ! $isSuper, function($q) use ($current) {
                $serviceIds = $this->getAccessibleServiceIds($current);
                $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);
                
                if ($serviceIds->isNotEmpty()) {
                    $q->whereHas('document', function($q2) use ($serviceIds) {
                        $q2->withTrashed()->whereIn('service_id', $serviceIds);
                    })
                    ->where(function($userQ) use ($allowedRoleNames, $current) {
                        // Include subordinates OR myself OR deleted users
                        $userQ->where('user_id', $current->id)
                              ->orWhereHas('user.roles', function($q2) use ($allowedRoleNames) {
                                  $q2->whereIn('name', $allowedRoleNames);
                              })
                              ->orWhereNull('user_id');
                    });
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

        $totalLogs = (clone $statsBase)->count();
        $todayLogs = (clone $statsBase)->whereDate('occurred_at', today())->count();
        $thisWeekLogs = (clone $statsBase)->whereBetween('occurred_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $uniqueUsers = (clone $statsBase)->distinct('user_id')->count('user_id');

        // Get filter options (respect department/service scoping and role hierarchy)
        if ($docStatsDeptScope && $deptIds->isNotEmpty() && ! $isSuper) {
            $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);
            
            $users = User::whereHas('departments', function($q) use ($deptIds) {
                    $q->whereIn('departments.id', $deptIds);
                })
                ->whereHas('roles', function($q) use ($allowedRoleNames) {
                    $q->whereIn('name', $allowedRoleNames);
                })
                ->orderBy('full_name')
                ->get();
            $departments = Department::whereIn('id', $deptIds)->orderBy('name')->get();
        } elseif ($isServiceAuditor && ! $docStatsDeptScope && ! $isSuper) {
            $serviceIds = $this->getAccessibleServiceIds($current);
            $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);
            
            if ($serviceIds->isNotEmpty()) {
                $users = User::where(function($q) use ($serviceIds, $current) {
                        // Include subordinate users in the service
                        $q->where(function($subQ) use ($serviceIds) {
                            $subQ->whereIn('service_id', $serviceIds)
                                 ->orWhereHas('services', function($sq) use ($serviceIds) {
                                     $sq->whereIn('services.id', $serviceIds);
                                 });
                        })
                        // OR the manager themselves
                        ->orWhere('id', $current->id);
                     })
                    ->where(function($q2) use ($allowedRoleNames, $current) {
                        // Subordinate roles OR the current user
                        $q2->whereHas('roles', function($q) use ($allowedRoleNames) {
                            $q->whereIn('name', $allowedRoleNames);
                        })
                        ->orWhere('id', $current->id);
                    })
                    ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END, full_name', [$current->id])
                    ->get();
            } else {
                $users = collect([$current]);
            }
            $departments = collect();
        } else {
            $isSuperAdminNotMaster = $current
                && $current->can('view organization wide reports')
                && ! $current->can('view any role');

            if ($isSuperAdminNotMaster) {
                $users = User::whereDoesntHave('roles', function($q) {
                        $q->whereRaw('LOWER(name) = ?', ['master']);
                    })
                    ->orderBy('full_name')
                    ->get();
            } else {
                $users = User::orderBy('full_name')->get();
            }
            $departments = Department::orderBy('name')->get();
        }
        
        // Get unique action types
        if ($this->logType === 'authentication') {
            $actionTypes = ['login_success', 'login_failed', 'logout'];
        } else {
            $actionTypes = AuditLog::distinct()->pluck('action')->sort()->values();
        }

        return view('livewire.activity-logs-table', compact('logs', 'totalLogs', 'todayLogs', 'thisWeekLogs', 'uniqueUsers', 'users', 'departments', 'actionTypes'));
    }
}

