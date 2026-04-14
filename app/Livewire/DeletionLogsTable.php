<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class DeletionLogsTable extends Component
{
    use WithPagination;

    // Filters
    public $search = '';
    public $creationDate = '';
    public $expirationDate = '';
    public $deletedAt = '';
    public $deletedBy = '';
    public $departmentId = '';
    public $perPage = 25;
    public $documentId = null; // Filter by specific document ID

    protected $queryString = [
        'search' => ['except' => ''],
        'creationDate' => ['except' => ''],
        'expirationDate' => ['except' => ''],
        'deletedAt' => ['except' => ''],
        'deletedBy' => ['except' => ''],
        'departmentId' => ['except' => ''],
        'perPage' => ['except' => 25],
        'documentId' => ['except' => null, 'as' => 'document_id'],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('access document expiration management'), 403);
    }

    public function updated($field)
    {
        if (in_array($field, [
            'search', 'creationDate', 'expirationDate',
            'deletedAt', 'deletedBy', 'departmentId', 'perPage', 'documentId'
        ])) {
            $this->resetPage();
        }
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->creationDate = '';
        $this->expirationDate = '';
        $this->deletedAt = '';
        $this->deletedBy = '';
        $this->departmentId = '';
        $this->perPage = 25;
        $this->documentId = null;
        $this->resetPage();
    }

    private function buildQuery()
    {
        $current = auth()->user();

        $hasOrgReport = $current && $current->can('view organization wide reports');
        $isMaster = $current && $current->can('view any role');
        $isSuperAdminNotMaster = $hasOrgReport && ! $isMaster;

        $deptDeletionScope = $current && (
            $current->can('filter audit logs by assigned departments')
            || $current->can('filter audit logs by assigned subdepartments')
        );
        $isServiceAuditor = $current && $current->can('filter audit logs by assigned services');

        return AuditLog::with(['user.roles', 'document' => function ($q) {
                $q->withoutGlobalScopes()->withTrashed()->with(['department', 'service.subDepartment']);
            }])
            ->where('action', 'permanently_deleted')
            ->when($isSuperAdminNotMaster, function($q) {
                $q->whereDoesntHave('user.roles', function($r) {
                    $r->whereRaw('LOWER(name) = ?', ['master']);
                });
            })
            ->when($deptDeletionScope && ! $hasOrgReport, function($q) use ($current) {
                $deptIds = $current->departments?->pluck('id') ?? collect();
                
                $q->where(function($subQuery) use ($deptIds) {
                    // Filter by document department
                    $subQuery->whereHas('document', function($q2) use ($deptIds) {
                        $q2->withoutGlobalScopes()->withTrashed()->whereIn('department_id', $deptIds);
                    });
                })
                // Hide logs from Master, Super Admin, and Admin users
                ->whereDoesntHave('user.roles', function($r) {
                    $r->whereIn(\DB::raw('LOWER(name)'), ['master', 'super administrator', 'super_admin', 'admin']);
                });
            })
            ->when($isServiceAuditor && ! $deptDeletionScope && ! $hasOrgReport, function($q) use ($current) {
                $serviceIds = collect();
                if ($current->service_id) {
                    $serviceIds->push($current->service_id);
                }
                if ($current->relationLoaded('services') || method_exists($current, 'services')) {
                   $serviceIds = $serviceIds->merge($current->services->pluck('id'));
                }
                $serviceIds = $serviceIds->unique()->filter();

                if ($serviceIds->isNotEmpty()) {
                    $q->whereHas('document', function($q2) use ($serviceIds) {
                        $q2->withoutGlobalScopes()->withTrashed()->whereIn('service_id', $serviceIds);
                    })
                    // Hide logs from Master, Super Admin, Admin, and Dept Admin users
                    ->whereDoesntHave('user.roles', function($r) {
                        $r->whereIn(\DB::raw('LOWER(name)'), ['master', 'super administrator', 'super_admin', 'admin', 'admin de pole', 'department administrator']);
                    });
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            // Search by document title
            ->when($this->search, function($q) {
                $q->whereHas('document', function($q2) {
                    $q2->withTrashed()->where('title', 'like', '%' . $this->search . '%');
                });
            })
            // Filter by creation date
            ->when($this->creationDate, function($q) {
                $q->whereHas('document', function($q2) {
                    $q2->withTrashed()->whereDate('created_at', $this->creationDate);
                });
            })
            // Filter by expiration date
            ->when($this->expirationDate, function($q) {
                $q->whereHas('document', function($q2) {
                    $q2->withTrashed()->whereDate('expire_at', $this->expirationDate);
                });
            })
            // Filter by deleted at date
            ->when($this->deletedAt, function($q) {
                $q->whereDate('occurred_at', $this->deletedAt);
            })
            // Filter by user who deleted
            ->when($this->deletedBy, function($q) {
                $q->where('user_id', $this->deletedBy);
            })
            // Filter by department (structure)
            ->when($this->departmentId, function($q) {
                $q->whereHas('document', function($q2) {
                    $q2->withTrashed()->where('department_id', $this->departmentId);
                });
            })
            // Filter by specific document ID (from audit page)
            ->when($this->documentId, function($q) {
                $q->where('document_id', $this->documentId);
            })
            ->orderByDesc('occurred_at');
    }

    public function export()
    {
        abort_unless(auth()->user()?->can('access document expiration management'), 403);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\DeletionLogsExport($this->buildQuery()->get()),
            'deletion-logs-' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function exportSinglePdf($logId)
    {
        $user = auth()->user();
        abort_unless($user && $user->can('access document expiration management'), 403);

        // Set locale for translations in PDF
        app()->setLocale($user->locale ?? 'fr');

        $log = AuditLog::with(['user', 'document' => function ($q) {
                $q->withTrashed()->with(['department', 'service.subDepartment']);
            }])
            ->where('action', 'permanently_deleted')
            ->findOrFail($logId);

        $doc = $log->document;
        $dept = $doc?->department;
        $service = $doc?->service;
        $subDept = $service?->subDepartment;

        // Build structure string
        $structure = collect([$dept?->name, $subDept?->name, $service?->name])
            ->filter()
            ->implode(' > ');

        $html = view('pdf.deletion-log-single', [
            'log' => $log,
            'doc' => $doc,
            'structure' => $structure ?: '—',
        ])->render();

        // Configure mPDF for multi-language support (including Arabic/RTL)
        $isArabic = ($user->locale ?? 'fr') === 'ar';
        
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
            'default_font' => 'dejavusans', // Supports Arabic and special characters
            'directionality' => $isArabic ? 'rtl' : 'ltr', // RTL for Arabic
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);
        $mpdf->WriteHTML($html);

        return response()->streamDownload(
            fn () => print($mpdf->Output('', 'S')),
            'deletion-log-' . $log->id . '-' . now()->format('Ymd_His') . '.pdf'
        );
    }

    public function render()
    {
        $logs = $this->buildQuery()->paginate($this->perPage);

        $current = auth()->user();

        $hasOrgReport = $current && $current->can('view organization wide reports');
        $isMaster = $current && $current->can('view any role');
        $isSuperAdminNotMaster = $hasOrgReport && ! $isMaster;

        $deptDeletionScope = $current && (
            $current->can('filter audit logs by assigned departments')
            || $current->can('filter audit logs by assigned subdepartments')
        );
        $isServiceAuditor = $current && $current->can('filter audit logs by assigned services');

        $statsBase = AuditLog::where('action', 'permanently_deleted');

        if ($isSuperAdminNotMaster) {
            $statsBase->whereDoesntHave('user.roles', function($r) {
                $r->whereRaw('LOWER(name) = ?', ['master']);
            });
        }
        
        if ($deptDeletionScope && ! $hasOrgReport) {
            $deptIds = $current->departments?->pluck('id') ?? collect();

            $statsBase->where(function($subQuery) use ($deptIds) {
                // Filter by document department
                $subQuery->whereHas('document', function($q2) use ($deptIds) {
                    $q2->withTrashed()->whereIn('department_id', $deptIds);
                });
            })
            // Hide logs from Master, Super Admin, and Admin users (same as main query)
            ->whereDoesntHave('user.roles', function($r) {
                $r->whereIn(\DB::raw('LOWER(name)'), ['master', 'super administrator', 'super_admin', 'admin']);
            });
        } elseif ($isServiceAuditor && ! $deptDeletionScope && ! $hasOrgReport) {
             $serviceIds = collect();
            if ($current->service_id) {
                $serviceIds->push($current->service_id);
            }
            if ($current->relationLoaded('services') || method_exists($current, 'services')) {
               $serviceIds = $serviceIds->merge($current->services->pluck('id'));
            }
            $serviceIds = $serviceIds->unique()->filter();

            if ($serviceIds->isNotEmpty()) {
                 $statsBase->whereHas('document', function($q2) use ($serviceIds) {
                    $q2->withTrashed()->whereIn('service_id', $serviceIds);
                })
                // Hide logs from Master, Super Admin, Admin, and Dept Admin users (same as main query)
                ->whereDoesntHave('user.roles', function($r) {
                    $r->whereIn(\DB::raw('LOWER(name)'), ['master', 'super administrator', 'super_admin', 'admin', 'admin de pole', 'department administrator']);
                });
            } else {
                 $statsBase->whereRaw('1 = 0');
            }
        }
        
        $totalDeleted = (clone $statsBase)->count();
        $thisWeekDeleted = (clone $statsBase)
            ->whereBetween('occurred_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
        $todayDeleted = (clone $statsBase)
            ->whereDate('occurred_at', today())
            ->count();

        if ($deptDeletionScope && ! $hasOrgReport) {
            $deptIds = $current->departments?->pluck('id') ?? collect();

            $users = User::whereHas('departments', function($q) use ($deptIds) {
                    $q->whereIn('departments.id', $deptIds);
                })
                // Show all users in the department, regardless of rank
                ->orderBy('full_name')
                ->get();
            $departments = Department::whereIn('id', $deptIds)->orderBy('name')->get();
        } elseif ($isServiceAuditor) {
            // Service Manager: restricted dropdowns
             $serviceIds = collect();
            if ($current->service_id) {
                $serviceIds->push($current->service_id);
            }
            if ($current->relationLoaded('services') || method_exists($current, 'services')) {
               $serviceIds = $serviceIds->merge($current->services->pluck('id'));
            }
            $serviceIds = $serviceIds->unique()->filter();
            
            // Users: only subordinates in my services
            // Assuming getAccessibleServiceIds or similar logic logic for users
            // For now, simplify to users in my service. 
            // In ActivityLogsTable we used a complex query. Let's start simple: specific service users.
            $users = User::whereHas('services', function($q) use ($serviceIds) {
                    $q->whereIn('services.id', $serviceIds);
                 })->orWhereIn('service_id', $serviceIds)
                 ->orderBy('full_name')
                 ->get();

             // Only relevant departments (departments OF my services)
             $deptIdsFromServices = \App\Models\Service::whereIn('id', $serviceIds)->with('subDepartment.department')->get()->pluck('subDepartment.department.id')->unique();
             $departments = Department::whereIn('id', $deptIdsFromServices)->orderBy('name')->get();

        } else {
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

        return view('livewire.deletion-logs-table', compact(
            'logs', 
            'totalDeleted', 
            'thisWeekDeleted', 
            'todayDeleted', 
            'users', 
            'departments'
        ));
    }
}
