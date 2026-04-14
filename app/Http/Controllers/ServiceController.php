<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Service;
use App\Models\SubDepartment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ServiceController extends Controller
{
    /**
     * Store a new service.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $hasGlobalOrgAccess = $user?->can('view any role') || $user?->can('view organization wide reports');
        $isAdminDePoleScoped = $user?->can('view any department') && ! $hasGlobalOrgAccess;

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'scope_type' => 'nullable|in:sub,direct',
            'sub_department_id' => 'nullable|exists:sub_departments,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $scopeType = $data['scope_type'] ?? 'sub';
        $targetSubDepartmentId = null;

        // Scoped "Admin de pôle" can only create services in assigned poles.
        if ($isAdminDePoleScoped) {
            $assignedDeptIds = $user->departments->pluck('id')->toArray();

            if ($scopeType === 'direct') {
                $departmentId = (int) ($data['department_id'] ?? 0);
                if (! in_array($departmentId, $assignedDeptIds, true)) {
                    abort(403, 'You can only create direct services in your assigned pole.');
                }

                $targetSubDepartmentId = $this->resolveDirectSubDepartment($departmentId)->id;
            } else {
                $subDept = SubDepartment::find((int) ($data['sub_department_id'] ?? 0));
                if (! $subDept || ! in_array($subDept->department_id, $assignedDeptIds, true)) {
                    abort(403, 'You can only create services in your assigned pole.');
                }
                $targetSubDepartmentId = $subDept->id;
            }
        } elseif (! $hasGlobalOrgAccess) {
            Gate::authorize('create', Service::class);

            if ($scopeType === 'direct') {
                $departmentId = (int) ($data['department_id'] ?? 0);
                if (! Department::query()->whereKey($departmentId)->exists()) {
                    return back()->withErrors(['department_id' => __('Le pôle est requis pour un service direct.')]);
                }
                $targetSubDepartmentId = $this->resolveDirectSubDepartment($departmentId)->id;
            } else {
                $subDeptId = (int) ($data['sub_department_id'] ?? 0);
                if (! SubDepartment::query()->whereKey($subDeptId)->exists()) {
                    return back()->withErrors(['sub_department_id' => __('La sous-structure est requise.')]);
                }
                $targetSubDepartmentId = $subDeptId;
            }
        }

        Service::create([
            'name' => $data['name'],
            'sub_department_id' => $targetSubDepartmentId,
        ]);

        return redirect()->route('departments.index')->with('success', 'Service created.');
    }

    private function resolveDirectSubDepartment(int $departmentId): SubDepartment
    {
        return SubDepartment::firstOrCreate(
            [
                'department_id' => $departmentId,
                'name' => '__DIRECT__',
            ]
        );
    }

    /**
     * Update an existing service.
     */
    public function update(Request $request, Service $service)
    {
        Gate::authorize('update', $service);

        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $service->update($data);

        return redirect()->route('departments.index')->with('success', 'Service updated.');
    }

    /**
     * Delete a service.
     */
    public function destroy(Service $service)
    {
        Gate::authorize('delete', $service);

        $service->delete();

        return redirect()->route('departments.index')->with('success', 'Service deleted.');
    }
}
