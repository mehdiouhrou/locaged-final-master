<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Models\Department;
use App\Models\WorkFlowRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class WorkFlowRuleController extends Controller
{
    public function byDepartment($departmentId)
    {
        $department = Department::with('users')->findOrFail($departmentId);

        $rules = WorkFlowRule::with(['department', 'category', 'approverUser'])
            ->where('department_id', $departmentId)
            ->orderBy('category_id')
            ->orderBy('level')
            ->get()
            ->groupBy(function($rule) {
                return ($rule->category_id ?: 'all') . '_' . $rule->from_status . '_' . $rule->to_status;
            });

        $categories = \App\Models\Category::all();
        $roles = \Spatie\Permission\Models\Role::all();
        $users = $department->users;

        return view('workflow_rules.by-department', compact('rules', 'department', 'categories', 'roles', 'users'));
    }

    public function store(Request $request, $departmentId)
    {
        $request->validate([
            'from_status' => ['required', new Enum(DocumentStatus::class)],
            'to_status' => ['required', new Enum(DocumentStatus::class), 'different:from_status'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'levels' => ['required', 'array', 'min:1', 'max:6'],
            'levels.*.approver_type' => ['required', 'in:role,user'],
            'levels.*.approver_role' => ['required_if:levels.*.approver_type,role'],
            'levels.*.approver_user_id' => ['required_if:levels.*.approver_type,user', 'nullable', 'exists:users,id'],
            'levels.*.min_amount' => ['nullable', 'numeric', 'min:0'],
            'levels.*.max_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $department = Department::findOrFail($departmentId);

        DB::transaction(function () use ($request, $department) {
            foreach ($request->levels as $index => $levelData) {
                WorkFlowRule::create([
                    'department_id' => $department->id,
                    'category_id' => $request->category_id,
                    'from_status' => $request->from_status,
                    'to_status' => $request->to_status,
                    'level' => $index + 1,
                    'approver_role' => $levelData['approver_type'] === 'role' ? $levelData['approver_role'] : null,
                    'approver_user_id' => $levelData['approver_type'] === 'user' ? $levelData['approver_user_id'] : null,
                    'min_amount' => $levelData['min_amount'] ?? null,
                    'max_amount' => $levelData['max_amount'] ?? null,
                ]);
            }
        });

        return redirect()->back()->with('success', 'Workflow rule created successfully.');
    }


    public function update(Request $request, WorkFlowRule $workflowRule)
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'level' => ['nullable', 'integer', 'min:1', 'max:10'],
            'approver_role' => ['nullable', 'string', 'max:120'],
            'from_status' => [
                'required',
                new Enum(DocumentStatus::class),
                Rule::unique('workflow_rules')
                    ->ignore($workflowRule->id)
                    ->where(function ($query) use ($workflowRule, $request) {
                        return $query->where('department_id', $workflowRule->department_id)
                        ->where('category_id', $request->category_id)
                        ->where('level', (int) ($request->level ?: 1))
                        ->where('to_status', $request->to_status);
                    }),
                'different:to_status',
            ],
            'to_status' => [
                'required',
                new Enum(DocumentStatus::class),
            ],
        ]);


        $validated['level'] = (int) ($validated['level'] ?? 1);
        $workflowRule->update($validated);

        return redirect()->back()->with('success', 'Workflow rule updated successfully.');
    }

    public function destroy(WorkFlowRule $workflowRule)
    {
        $workflowRule->delete();

        return redirect()->back()->with('success', 'Workflow rule deleted successfully.');
    }
}
