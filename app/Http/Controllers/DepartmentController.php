<?php

namespace App\Http\Controllers;

use App\Imports\OrgStructureImport;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

class DepartmentController extends Controller
{
    // List all departments and related hierarchy
    public function index()
    {
        Gate::authorize('viewAny', Department::class);

        $user = auth()->user();

        $hasGlobalOrgAccess = $user?->can('view any role') || $user?->can('view organization wide reports');
        $isAdminDePoleScoped = $user?->can('view any department') && ! $hasGlobalOrgAccess;

        // Most admins can create structures; pole creation strictly follows policy.
        $canCreateStructures = true;
        $canCreatePole = (bool) ($user?->can('create', Department::class));

        // Eager-load sub-departments and services for tree view
        $departmentsQuery = Department::with([
            'subDepartments.services.users',
            'subDepartments.services.usersViaPivot',
        ]);

        // Filter to only assigned departments for Admin de pole
        if ($isAdminDePoleScoped && $user->departments && $user->departments->isNotEmpty()) {
            $departmentsQuery->whereIn('id', $user->departments->pluck('id'));
        }

        $departments = $departmentsQuery->latest()->paginate(10);

        // For Admin de pole: only show their assigned departments in dropdowns
        // For other admins: show all departments
        if ($isAdminDePoleScoped && $user->departments && $user->departments->isNotEmpty()) {
            $allDepartments = Department::with([
                'subDepartments.services',
            ])
                ->whereIn('id', $user->departments->pluck('id'))
                ->orderBy('name')
                ->get();
        } else {
            $allDepartments = Department::with([
                'subDepartments.services',
            ])->orderBy('name')->get();
        }

        return view('departments.index', compact('departments', 'allDepartments', 'canCreateStructures', 'canCreatePole'));
    }

    public function importOrgChart(Request $request)
    {
        Gate::authorize('viewAny', Department::class);

        $request->validate([
            'org_chart' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        Excel::import(new OrgStructureImport, $request->file('org_chart'));

        return redirect()->route('departments.index')
            ->with('success', __('Organigramme importé : pôles, sous-structures et services ont été créés ou réassociés selon le fichier (colonnes A / B / C).'));
    }

    // Store a new department
    public function store(Request $request)
    {
        Gate::authorize('create', Department::class);


        $data = $request->validate([
            'name'        => 'required|string|max:255|unique:departments,name',
            'description' => 'nullable|string|max:1000',
        ]);

        Department::create($data);

        return redirect()->route('departments.index')->with('success', 'Department created.');
    }



    // Update a department
    public function update(Request $request, Department $department)
    {
        // Authorize against the specific department model instance
        Gate::authorize('update', $department);

        $data = $request->validate([
            'name'        => 'required|string|max:255|unique:departments,name,' . $department->id,
            'description' => 'nullable|string|max:1000',
        ]);

        $department->update($data);

        return redirect()->route('departments.index')->with('success', 'Department updated.');
    }

    // Delete a department
    public function destroy(Department $department)
    {
        // Authorize against the specific department model instance
        Gate::authorize('delete', $department);

        $department->delete();

        return redirect()->route('departments.index')->with('success', 'Department deleted.');
    }
}
