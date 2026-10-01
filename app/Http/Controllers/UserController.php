<?php

namespace App\Http\Controllers;

use App\Exports\UsersExport;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\User;
use App\Support\Branding;
use App\Support\RoleHierarchy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\PdfExportService;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    // List all users
    public function index()
    {
        Gate::authorize('viewAny',User::class);

        $current = auth()->user();
        $allowedRoleNames = RoleHierarchy::allowedRoleNamesFor($current);

        $users = User::with('roles')
            ->when(!empty($allowedRoleNames), function ($q) use ($allowedRoleNames) {
                $q->whereHas('roles', function ($qr) use ($allowedRoleNames) {
                    $qr->whereIn('name', $allowedRoleNames);
                });
            }, function ($q) {
                // If current user has no allowed roles (should not happen), hide all
                $q->whereRaw('1 = 0');
            })
            ->paginate(10);

        $roles = Role::select('name','id')
            ->when(auth()->check(), function ($q) {
                // For assignment, only allow roles strictly below current user's rank (except master)
                $allowed = RoleHierarchy::allowedAssignableRoleNamesFor(auth()->user());
                if (!empty($allowed)) {
                    $q->whereIn('name', $allowed);
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            ->get();

        if ($current->roles->contains('name', 'Department Administrator')) {
            $roles = $roles->reject(fn($r) => strtolower($r->name) === 'department administrator');
        }
        if ($current->roles->contains('name', 'Super Administrator')) {
            $roles = $roles->reject(fn($r) => strtolower($r->name) === 'super administrator');
        }

        // Limit visible org structure based on creator role
        $departmentsQuery = Department::withoutGlobalScopes()->with('subDepartments.services');

        if ($current->can('view any role') || $current->can('view organization wide reports')) {
            $departments = $departmentsQuery->get();
        } else {
            // Base department IDs on explicit department assignments first
            $deptIds = DB::table('department_user')
                ->where('user_id', $current->id)
                ->pluck('department_id');

            // Gather services and sub-departments explicitly assigned
            $serviceIds = DB::table('service_user')
                ->where('user_id', $current->id)
                ->pluck('service_id');

            $explicitSubDeptIds = DB::table('sub_department_user')
                ->where('user_id', $current->id)
                ->pluck('sub_department_id');

            // Also infer sub-departments from assigned services
            $serviceSubDeptIds = $serviceIds->isNotEmpty()
                ? DB::table('services')->whereIn('id', $serviceIds)->pluck('sub_department_id')
                : collect();

            $allSubDeptIds = $explicitSubDeptIds->merge($serviceSubDeptIds)->unique();

            // If no departments are directly assigned, infer them from sub-departments
            if ($deptIds->isEmpty() && $allSubDeptIds->isNotEmpty()) {
                $deptIds = DB::table('sub_departments')
                    ->whereIn('id', $allSubDeptIds)
                    ->pluck('department_id');
            }

            $isDivisionChief = $current->can('view subdepartment scoped documents');
            $isServiceManager = $current->can('view service user');

            if ($isDivisionChief || $isServiceManager) {
                // Division Chief & Service Manager: only own departments and own sub-departments
                $departments = $departmentsQuery
                    ->whereIn('id', $deptIds)
                    ->get()
                    ->map(function ($dept) use ($allSubDeptIds, $isServiceManager, $serviceIds) {
                        $subDepts = $dept->subDepartments
                            ->whereIn('id', $allSubDeptIds)
                            ->values();

                        // For service managers, also restrict services to those explicitly assigned
                        if ($isServiceManager && $serviceIds->isNotEmpty()) {
                            $subDepts->each(function ($subDept) use ($serviceIds) {
                                $subDept->setRelation(
                                    'services',
                                    $subDept->services->whereIn('id', $serviceIds)->values()
                                );
                            });
                        }

                        $dept->setRelation('subDepartments', $subDepts);
                        return $dept;
                    });
            } else {
                // Department Admin, Service User and others:
                // only own departments; keep all sub-departments/services under them
                $departments = $departmentsQuery
                    ->whereIn('id', $deptIds)
                    ->get();
            }
        }

        return view('users.index',compact('users','roles','departments'));
    }

    public function export(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        return Excel::download(new UsersExport($request), 'users-' . now()->format('Ymd_His') . '.xlsx');
    }

    public function exportPdf(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        $export = new UsersExport($request);
        $rows = $export->query()->get()->map(fn($u) => $export->map($u))->toArray();

        return (new PdfExportService)->download(
            'Rapport Utilisateurs',
            $export->headings(),
            $rows,
            'utilisateurs-' . now()->format('Ymd_His'),
            ['Export g\xc3\xa9n\xc3\xa9r\xc3\xa9 le ' . now()->format('d/m/Y \xc3\xa0 H:i')]
        );
    }


    public function exportActivityLogsPdf(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        $logType = $request->get('logType', 'documents');
        $search = $request->get('search', '');
        $dateFrom = $request->get('dateFrom', '');
        $dateTo = $request->get('dateTo', '');
        $userId = $request->get('userId', '');
        $departmentId = $request->get('departmentId', '');
        $actionType = $request->get('actionType', '');

        $current = auth()->user();
        $hasOrgReport = $current && $current->can('view organization wide reports');
        $isMaster = $current && $current->can('view any role');
        $isSuperAdminNotMaster = $hasOrgReport && !$isMaster;

        if ($logType === 'authentication') {
            $isPoleOrDeptAuditor = $current && $current->can('filter audit logs by assigned departments');
            $isSubdeptAuditor = $current && $current->can('filter audit logs by assigned subdepartments');
            $isServiceAuditor = $current && $current->can('filter audit logs by assigned services');

            $query = \App\Models\AuthenticationLog::with(['user', 'user.roles'])
                ->when($isSuperAdminNotMaster, function($q) {
                    $q->whereDoesntHave('user.roles', function($r) {
                        $r->whereRaw('LOWER(name) = ?', ['master']);
                    });
                })
                ->when($isPoleOrDeptAuditor && !$hasOrgReport, function($q) use ($current) {
                    $deptIds = $current->departments?->pluck('id') ?? collect();
                    $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);
                    $q->where(function($q1) use ($deptIds, $allowedRoleNames) {
                        $q1->whereHas('user', function($q2) use ($deptIds, $allowedRoleNames) {
                            $q2->whereHas('departments', function($q3) use ($deptIds) {
                                $q3->whereIn('departments.id', $deptIds);
                            })->whereHas('roles', function($q3) use ($allowedRoleNames) {
                                $q3->whereIn('name', $allowedRoleNames);
                            });
                        })->orWhereNull('user_id');
                    });
                })
                ->when($isSubdeptAuditor && !$isPoleOrDeptAuditor && !$hasOrgReport, function($q) use ($current) {
                    $subDeptIds = $current->subDepartments?->pluck('id') ?? collect();
                    $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);
                    $q->where(function($q1) use ($subDeptIds, $allowedRoleNames) {
                        $q1->whereHas('user', function($q2) use ($subDeptIds, $allowedRoleNames) {
                            $q2->whereHas('subDepartments', function($q3) use ($subDeptIds) {
                                $q3->whereIn('sub_departments.id', $subDeptIds);
                            })->whereHas('roles', function($q3) use ($allowedRoleNames) {
                                $q3->whereIn('name', $allowedRoleNames);
                            });
                        })->orWhereNull('user_id');
                    });
                })
                ->when($isServiceAuditor && !$isPoleOrDeptAuditor && !$isSubdeptAuditor && !$hasOrgReport, function($q) use ($current) {
                    $serviceIds = collect();
                    if ($current->service_id) $serviceIds->push($current->service_id);
                    $serviceIds = $serviceIds->merge($current->services->pluck('id'))->unique()->filter();
                    $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);
                    $q->where(function($q1) use ($serviceIds, $allowedRoleNames, $current) {
                        $q1->whereHas('user', function($q2) use ($serviceIds, $allowedRoleNames, $current) {
                            $q2->where(function($query) use ($serviceIds, $allowedRoleNames, $current) {
                                $query->where(function($subQ) use ($serviceIds, $allowedRoleNames) {
                                    $subQ->where(function($sQ) use ($serviceIds) {
                                        $sQ->whereIn('users.service_id', $serviceIds)
                                           ->orWhereHas('services', function($pivot) use ($serviceIds) {
                                               $pivot->whereIn('services.id', $serviceIds);
                                           });
                                    })->whereHas('roles', function($r) use ($allowedRoleNames) {
                                        $r->whereIn('name', $allowedRoleNames);
                                    });
                                })->orWhere('users.id', $current->id);
                            });
                        })->orWhereNull('user_id');
                    });
                })
                ->when($dateFrom, fn($q) => $q->whereDate('occurred_at', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('occurred_at', '<=', $dateTo))
                ->when($userId, fn($q) => $q->where('user_id', $userId))
                ->when($actionType, fn($q) => $q->where('type', $actionType))
                ->when($search, function($q) use ($search) {
                    $q->where(function($q2) use ($search) {
                        $q2->where('email', 'like', '%'.$search.'%')
                           ->orWhere('ip_address', 'like', '%'.$search.'%')
                           ->orWhere('user_name', 'like', '%'.$search.'%')
                           ->orWhereHas('user', function($q3) use ($search) {
                               $q3->where('full_name', 'like', '%'.$search.'%');
                           });
                    });
                })
                ->latest('occurred_at');

            $logs = $query->get();
            $export = new \App\Exports\ActivityLogsExport($logs, 'authentication');
        } else {
            $isPoleOrDeptAuditor = $current && $current->can('filter audit logs by assigned departments');
            $isSubdeptAuditor = $current && $current->can('filter audit logs by assigned subdepartments');
            $isServiceAuditor = $current && $current->can('filter audit logs by assigned services');

            $query = \App\Models\AuditLog::with([
                    'user.departments', 'user.roles',
                    'document' => function($q) { $q->withoutGlobalScopes()->withTrashed(); },
                    'document.department', 'documentVersion'
                ])
                ->where('action', '!=', 'viewed_ocr')
                ->where(function($q) {
                    $q->whereNull('subject_type')->orWhere('subject_type', '!=', 'authentication');
                })
                ->when($isSuperAdminNotMaster, function($q) {
                    $q->whereDoesntHave('user.roles', function($r) {
                        $r->whereRaw('LOWER(name) = ?', ['master']);
                    });
                });

            $query->when($isPoleOrDeptAuditor && !$hasOrgReport, function($q) use ($current) {
                $deptIds = $current->departments?->pluck('id') ?? collect();
                $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);
                if ($deptIds->isNotEmpty()) {
                    $q->where(function($q2) use ($deptIds) {
                        $q2->whereHas('document', function($q3) use ($deptIds) {
                            $q3->withTrashed()->whereIn('documents.department_id', $deptIds);
                        });
                    })->where(function($userQ) use ($allowedRoleNames) {
                        $userQ->whereHas('user.roles', function($q2) use ($allowedRoleNames) {
                            $q2->whereIn('name', $allowedRoleNames);
                        })->orWhereNull('user_id');
                    });
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            $query->when($isSubdeptAuditor && !$isPoleOrDeptAuditor && !$hasOrgReport, function($q) use ($current) {
                $subDeptIds = $current->subDepartments?->pluck('id') ?? collect();
                $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);
                if ($subDeptIds->isNotEmpty()) {
                    $serviceIds = \App\Models\Service::whereIn('sub_department_id', $subDeptIds)->pluck('id');
                    $q->where(function($q2) use ($serviceIds) {
                        $q2->whereHas('document', function($q3) use ($serviceIds) {
                            $q3->withTrashed()->whereIn('documents.service_id', $serviceIds);
                        });
                    })->where(function($userQ) use ($subDeptIds, $allowedRoleNames) {
                        $userQ->whereHas('user', function($q2) use ($subDeptIds, $allowedRoleNames) {
                            $q2->whereHas('subDepartments', function($q3) use ($subDeptIds) {
                                $q3->whereIn('sub_departments.id', $subDeptIds);
                            })->whereHas('roles', function($q3) use ($allowedRoleNames) {
                                $q3->whereIn('name', $allowedRoleNames);
                            });
                        })->orWhereNull('user_id');
                    });
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            $query->when($isServiceAuditor && !$isPoleOrDeptAuditor && !$isSubdeptAuditor && !$hasOrgReport, function($q) use ($current) {
                $serviceIds = collect();
                if ($current->service_id) $serviceIds->push($current->service_id);
                $serviceIds = $serviceIds->merge($current->services->pluck('id'))->unique()->filter();
                $allowedRoleNames = \App\Support\RoleHierarchy::allowedRoleNamesFor($current);
                if ($serviceIds->isNotEmpty()) {
                    $q->whereHas('document', function($d) use ($serviceIds) {
                        $d->withTrashed()->whereIn('documents.service_id', $serviceIds);
                    })->where(function($u) use ($allowedRoleNames, $current) {
                        $u->where('user_id', $current->id)
                          ->orWhereHas('user.roles', function($r) use ($allowedRoleNames) {
                              $r->whereIn('name', $allowedRoleNames);
                          })->orWhereNull('user_id');
                    });
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            $query->when($dateFrom, fn($q) => $q->whereDate('occurred_at', '>=', $dateFrom))
                  ->when($dateTo, fn($q) => $q->whereDate('occurred_at', '<=', $dateTo))
                  ->when($userId, fn($q) => $q->where('user_id', $userId))
                  ->when($departmentId, function($q) use ($departmentId) {
                      $q->whereHas('document', function($q2) use ($departmentId) {
                          $q2->where('department_id', $departmentId);
                      });
                  })
                  ->when($actionType, fn($q) => $q->where('action', $actionType))
                  ->when($search, function($q) use ($search) {
                      $term = '%'.$search.'%';
                      $q->where(function($q2) use ($term) {
                          $q2->whereHas('user', function($q3) use ($term) {
                              $q3->where('full_name', 'like', $term)->orWhere('email', 'like', $term);
                          })->orWhere('user_name', 'like', $term)
                            ->orWhereHas('document', function($q3) use ($term) {
                                $q3->where('title', 'like', $term);
                            })->orWhere('action', 'like', $term);
                      });
                  })
                  ->latest('occurred_at');

            $logs = $query->get();
            $export = new \App\Exports\ActivityLogsExport($logs, 'documents');
        }

        $rows = $logs->map(fn($log) => $export->map($log))->toArray();
        $title = $logType === 'authentication' ? 'Logs de Connexion' : 'Journal d\'Activité';

        return (new \App\Services\PdfExportService)->download(
            $title,
            $export->headings(),
            $rows,
            'activity-logs-' . now()->format('Ymd_His'),
            ['Export généré le ' . now()->format('d/m/Y à H:i')]
        );
    }

    // Show a specific user
    public function show(User $user)
    {
        Gate::authorize('view',$user);

        $departments = Department::all();
        // For assignment in profile, use assignable roles (strictly lower rank except master)
        $roles = Role::whereIn('name', RoleHierarchy::allowedAssignableRoleNamesFor(auth()->user()))->get();

        // Extra safety: drop same-rank role from list when editing as dep/super admin
        $current = auth()->user();
        if ($current->roles->contains('name', 'Department Administrator')) {
            $roles = $roles->reject(fn($r) => strtolower($r->name) === 'department administrator');
        }
        if ($current->roles->contains('name', 'Super Administrator')) {
            $roles = $roles->reject(fn($r) => strtolower($r->name) === 'super administrator');
        }
        return view('users.profile',compact('user','departments','roles'));
    }

    /**
     * Show the authenticated user's own profile.
     * This route is accessible to all authenticated users.
     */
    public function showOwnProfile()
    {
        $user = auth()->user();
        
        if (!$user) {
            abort(403);
        }

        $departments = Department::all();
        // Minimal roles for self-view (not editable by regular users)
        $roles = collect();

        return view('users.profile', compact('user', 'departments', 'roles'));
    }

    /**
     * Update the authenticated user's own profile.
     * This route is accessible to all authenticated users for basic profile updates.
     */
    public function updateOwnProfile(Request $request)
    {
        $user = auth()->user();
        
        if (!$user) {
            abort(403);
        }

        $data = $request->validate([
            'full_name'  => 'string|max:255',
            'phone' => 'nullable|string|max:255',
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:8384'],
        ]);

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('profile', 'public');
            $data['image'] = $path;
        }
        unset($data['profile_image']);

        $user->update($data);

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    public function audit()
    {
        Gate::authorize('viewAny',User::class);

        if (auth()->user()?->can('view subdepartment scoped documents')) {
            abort(403);
        }

        $current = auth()->user();
        $allowedRoleNames = RoleHierarchy::allowedRoleNamesFor($current);
        $isDeptAdmin = $current
            && $current->can('filter audit logs by assigned departments')
            && ! $current->can('view organization wide reports')
            && ! $current->can('view any role');
        
        $usersQuery = User::with('roles');
        
        // Department Administrator: only see users from their departments with lower roles
        if ($isDeptAdmin) {
            $deptIds = $current->departments?->pluck('id') ?? collect();
            
            $usersQuery->when($deptIds->isNotEmpty(), function($q) use ($deptIds, $allowedRoleNames) {
                // User must be in one of the admin's departments
                $q->whereHas('departments', function($q2) use ($deptIds) {
                    $q2->whereIn('departments.id', $deptIds);
                })
                // AND user must have a role below the admin's rank
                ->whereHas('roles', function($q2) use ($allowedRoleNames) {
                    $q2->whereIn('name', $allowedRoleNames);
                });
            }, function($q) {
                // If no departments assigned, show nothing
                $q->whereRaw('1 = 0');
            });
        } else {
            // Other users: apply role hierarchy filtering
            $usersQuery->when(!empty($allowedRoleNames), function ($q) use ($allowedRoleNames) {
                $q->whereHas('roles', function ($qr) use ($allowedRoleNames) {
                    $qr->whereIn('name', $allowedRoleNames);
                });
            }, function ($q) {
                $q->whereRaw('1 = 0');
            });
        }
        
        $users = $usersQuery->paginate(10);

        return view('users.audit',compact('users'));
    }

    public function activity($id)
    {
        $user = User::findOrFail($id);
        Gate::authorize('view',$user);

        $auditLogs = $user->auditLogs()->with('document')->latest('occurred_at')->paginate(10); // Adjust per-page as needed

        return view('users.activity',compact('user','auditLogs'));
    }

    public function logs()
    {
        Gate::authorize('viewAny',User::class);

        abort_unless(
            auth()->user()?->can('view audit log')
            || auth()->user()?->can('view system activity log'),
            403
        );

        return view('users.logs');
    }


    // Store a new user
    public function store(Request $request)
    {
        Gate::authorize('create',User::class);

        // Check user limit before validation
        $maxUsers = Branding::getMaxUsers();
        if ($maxUsers > 0) {
            $currentUserCount = User::count();
            if ($currentUserCount >= $maxUsers) {
                throw ValidationException::withMessages([
                    'email' => ["Cannot create user. Maximum number of users ({$maxUsers}) has been reached."],
                ]);
            }
        }

        $selectedRoleId = $request->input('role');
        $selectedRoleName = null;
        if ($selectedRoleId) {
            $selectedRole = Role::find($selectedRoleId);
            $selectedRoleName = $selectedRole?->name;
        }

        $normalizedRoleName = $selectedRoleName ? strtolower($selectedRoleName) : '';

        // Default: org structure optional
        $departmentsRule = 'nullable|array';
        $subDepartmentsRule = 'nullable|array';
        $servicesRule = 'nullable|array';

        // Department Administrator, Division Chief, Service Manager, Service User must have at least one department
        if (in_array($normalizedRoleName, ['chef de pôle', 'chef de département', 'chargée de dépôt', 'utilisateur'])) {
            $departmentsRule = 'required|array|min:1';
        }

        // Division Chief, Service Manager and Service User must have at least one sub-department
        if (in_array($normalizedRoleName, ['chef de département', 'chargée de dépôt', 'utilisateur'])) {
            $subDepartmentsRule = 'required|array|min:1';
        }

        // Service Manager and Service User must have at least one service
        if (in_array($normalizedRoleName, ['chargée de dépôt', 'utilisateur'])) {
            $servicesRule = 'required|array|min:1';
        }

        // NEW: Check if admin wants to set password now
        $setPasswordNow = $request->boolean('set_password_now', false);

        $data = $request->validate([
            'full_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            // Password is now conditionally required
            'password'   => $setPasswordNow 
                ? ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()]
                : ['nullable'],
            'active'     => 'nullable|boolean',
            'role'       => 'nullable|exists:roles,id',
            'departments'   => $departmentsRule,
            'departments.*' => 'exists:departments,id',
            'sub_departments'   => $subDepartmentsRule,
            'sub_departments.*' => 'exists:sub_departments,id',
            'services'          => $servicesRule,
            'services.*'        => 'exists:services,id',
            'set_password_now'  => 'nullable|boolean',
        ], [
            'departments.required' => 'At least one structure must be selected.',
            'departments.min' => 'At least one structure must be selected.',
            'sub_departments.required' => 'At least one sub-department must be selected for this role.',
            'sub_departments.min' => 'At least one sub-department must be selected for this role.',
            'service_id.required' => 'A service must be selected for this role.',
        ]);

        // Determine primary sub-department and service (first in each list)
        $primarySubDeptId = isset($data['sub_departments'][0]) ? $data['sub_departments'][0] : null;
        $primaryServiceId = isset($data['services'][0]) ? $data['services'][0] : null;

        // Store plain text password before hashing (for email)
        $plainPassword = $data['password'] ?? null;

        $user = User::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'active' => $data['active'] ?? true,
            'username' => $data['email'],
            // If password provided, hash it; otherwise set random password that user can't use
            'password' => $plainPassword ? Hash::make($plainPassword) : Hash::make(bin2hex(random_bytes(32))),
            'locale' => 'fr', // Default locale is French
            'sub_department_id' => $primarySubDeptId,
            'service_id'        => $primaryServiceId,
        ]);

        if (isset($data['role'])) {
            $role = Role::findOrFail($data['role']);
            if (!RoleHierarchy::canAssignRole(auth()->user(), $role)) {
                throw ValidationException::withMessages([
                    'role' => ['You are not allowed to assign this role.'],
                ]);
            }
            $user->assignRole($role->name);

            \App\Services\AuditService::logUserAction('user_role_changed', $user, [
                'previous_role' => null,
                'new_role' => $role->name,
            ]);
        }

        // Sync multiple departments
        $user->departments()->sync($data['departments'] ?? []);
        $user->subDepartments()->sync($data['sub_departments'] ?? ($primarySubDeptId ? [$primarySubDeptId] : []));
        $user->services()->sync($data['services'] ?? []);

        // NEW: Send appropriate email
        if ($setPasswordNow && $plainPassword) {
            // Admin set password: send credentials email
            \Mail::to($user->email)->send(new \App\Mail\UserCreatedWithPassword($user));
        } else {
            // Admin didn't set password: send invitation email with setup link
            $setupUrl = \URL::temporarySignedRoute(
                'password.setup.show',
                now()->addHours(24),
                ['user' => $user->id]
            );
            \Mail::to($user->email)->send(new \App\Mail\UserInvitation($user, $setupUrl));
        }

        return redirect()->back()->with('success', 'User created successfully. An email has been sent to the user.');
    }

        // Update an existing user (Admins from Users management UI)
    public function update(Request $request, User $user)
    {
        Gate::authorize('update',$user);

        $rawRoleId = $request->input('role_id') ?: $request->input('role');
        $rawRoleName = null;
        if ($rawRoleId) {
            $rawRole = Role::find($rawRoleId);
            $rawRoleName = $rawRole?->name;
        }
        $normalizedRoleName = $rawRoleName ? strtolower($rawRoleName) : '';

        // Default: org structure optional on update as well
        $departmentsRule = 'nullable|array';
        $subDepartmentsRule = 'nullable|array';
        $servicesRule = 'nullable|array';

        if (in_array($normalizedRoleName, ['chef de pôle', 'chef de département', 'chargée de dépôt', 'utilisateur'])) {
            $departmentsRule = 'required|array|min:1';
        }

        if (in_array($normalizedRoleName, ['chef de département', 'chargée de dépôt', 'utilisateur'])) {
            $subDepartmentsRule = 'required|array|min:1';
        }

        // Service Manager and Service User must have at least one service
        if (in_array($normalizedRoleName, ['chargée de dépôt', 'utilisateur'])) {
            $servicesRule = 'required|array|min:1';
        }

        $data = $request->validate([
            'full_name'  => 'string|max:255',
            'email'      => ['email', Rule::unique('users')->ignore($user->id)],
            // phone no longer required
            'phone' => 'nullable|string|max:255',
            'active'     => 'nullable|boolean',
            'role_id'       => 'nullable|exists:roles,id',
            // Note: department_id removed - now using multi-department system via pivot table
'departments'   => $departmentsRule,
            'departments.*' => 'exists:departments,id',
            'sub_departments'   => $subDepartmentsRule,
            'sub_departments.*' => 'exists:sub_departments,id',
            'services'          => $servicesRule,
            'services.*'        => 'exists:services,id',
            'password'              => ['nullable', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'password_confirmation' => ['nullable'],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:8384'],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        // Optional admin-led image update from Users management modal
        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('profile', 'public');
            $data['image'] = $path;
        }

        // Remove departments, sub-departments and services from mass assignment
        $departments     = $data['departments'] ?? null;
        $subDepartments  = $data['sub_departments'] ?? null;
        $services        = $data['services'] ?? null;
        unset($data['departments'], $data['sub_departments'], $data['services']);

        $previousRoles = $user->getRoleNames()->implode(', ');

        $user->update($data);

        // Support role coming as role_id (existing) or role (modal select)
        $roleId = $data['role_id'] ?? $request->input('role');
        if ($roleId) {
            $role = Role::findOrFail($roleId);
            if (!RoleHierarchy::canAssignRole(auth()->user(), $role)) {
                throw ValidationException::withMessages([
                    'role' => ['You are not allowed to assign this role.'],
                ]);
            }
            $user->syncRoles($role->name);

            if ($previousRoles !== $role->name) {
                \App\Services\AuditService::logUserAction('user_role_changed', $user, [
                    'previous_role' => $previousRoles ?: null,
                    'new_role' => $role->name,
                ]);
            }
        }

        // Sync multiple departments (may be optional depending on role)
        $user->departments()->sync($departments ?? []);

        // Sync sub-departments (multi-select via pivot)
        $user->subDepartments()->sync($subDepartments ?? []);

        // Sync services (multi-select)
        $user->services()->sync($services ?? []);

        // IMPORTANT: Also update the primary service_id and sub_department_id columns
        // to match the first selected service/sub-department. This ensures the dashboard
        // and other parts of the system that rely on these columns show the correct data.
        $primarySubDeptId = isset($subDepartments[0]) ? $subDepartments[0] : null;
        $primaryServiceId = isset($services[0]) ? $services[0] : null;
        
        $user->update([
            'sub_department_id' => $primarySubDeptId,
            'service_id' => $primaryServiceId,
        ]);

        return redirect()->back()->with('success', 'User updated successfully.');
    }

    public function updatePassword(Request $request, User $user)
    {
        Gate::authorize('updatePassword',$user);

        $data = $request->validate([
            'old_password'          => ['required', 'string'],
            'password'              => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        // Check old password
        if (!Hash::check($data['old_password'], $user->password)) {
            throw ValidationException::withMessages([
                'old_password' => ['The provided old password does not match our records.'],
            ]);
        }

        // Update new password
        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->back()->with('success', 'Password updated successfully.');
    }

    /**
     * Admin-triggered password reset for another user. Two modes:
     * - Email link (default): sends the standard Laravel reset link, admin never
     *   sees the new password. Requires the user to have a working email address.
     * - Manual (no_email=1): admin sets a temporary password directly, for users
     *   without functional email access. The password is NEVER emailed (security
     *   fix 21/08/2026) — the admin must communicate it out-of-band.
     * Both modes: logs the action to the target user's audit trail (visible on
     * their Activité page) and, when possible, notifies the user that their
     * password was changed by an administrator.
     */
    public function resetPassword(Request $request, User $user)
    {
        Gate::authorize('resetPassword', $user);

        $noEmail = $request->boolean('no_email');

        if ($noEmail) {
            $data = $request->validate([
                'password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            ]);

            $user->update([
                'password' => Hash::make($data['password']),
            ]);

            \App\Services\AuditService::logUserAction('password_reset_manual', $user, [
                'method' => 'manual',
            ]);

            try {
                \Mail::to($user->email)->send(new \App\Mail\PasswordChangedByAdmin($user));
            } catch (\Throwable $e) {
                // Silently ignore: user may not have a functional email address (no-email mode).
            }

            return redirect()->back()->with('success', ui_t('pages.users_page.user_modal.reset_password.password_reset_manual_success'));
        }

        \App\Services\AuditService::logUserAction('password_reset_link_sent', $user, [
            'method' => 'email_link',
        ]);

        $status = \Illuminate\Support\Facades\Password::sendResetLink(['email' => $user->email]);

        if ($status !== \Illuminate\Support\Facades\Password::RESET_LINK_SENT) {
            return redirect()->back()->with('error', __($status));
        }

        return redirect()->back()->with('success', ui_t('pages.users_page.user_modal.reset_password.password_reset_link_success'));
    }

    public function updateImage(Request $request, User $user)
    {
        $actor = auth()->user();
        if (! $actor) {
            abort(403);
        }

        // Allow user to update their own image, or users with update user permission
        if ($actor->id !== $user->id && ! $actor->can('update user')) {
            abort(403);
        }

        $data = $request->validate([
            'profile_image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:8384'],
        ]);

        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('profile', 'public');
            $user->image = $path;
            $user->save();
        }

        return redirect()->back()->with('success', 'Profile image updated successfully.');
    }


    /**
     * Désactivation logique (jamais de suppression physique de la ligne users).
     */
    public function destroy(User $user)
    {
        Gate::authorize('delete', $user);

        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', __('pages.users_page.cannot_deactivate_self'));
        }

        if (! $user->active) {
            return redirect()->back()->with('info', __('pages.users_page.already_inactive'));
        }

        $user->active = false;
        $user->save();

        return redirect()->back()->with('success', __('pages.users_page.user_deactivated'));
    }

    /**
     * Réactivation d’un compte désactivé (active = 1).
     */
    public function reactivate(User $user)
    {
        Gate::authorize('update', $user);

        if ($user->active) {
            return redirect()->back()->with('info', __('pages.users_page.already_active'));
        }

        $user->active = true;
        $user->save();

        return redirect()->back()->with('success', __('pages.users_page.user_reactivated'));
    }
}
