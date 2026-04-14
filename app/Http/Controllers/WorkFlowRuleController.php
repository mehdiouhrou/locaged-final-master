<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Models\Department;
use App\Models\WorkFlowRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class WorkFlowRuleController extends Controller
{
    public function byDepartment($departmentId)
    {
        $department = Department::findOrFail($departmentId);

        $rules = WorkFlowRule::with('department')->where('department_id', $departmentId)->paginate(10);
        return view('workflow_rules.by-department', compact('rules', 'department'));
    }

    public function store(Request $request, $departmentId)
    {

        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'level' => ['nullable', 'integer', 'min:1', 'max:10'],
            'approver_role' => ['nullable', 'string', 'max:120'],
            'from_status' => [
                'required',
                'different:to_status',
                new Enum(DocumentStatus::class),
                Rule::unique('workflow_rules')
                    ->where(function ($query) use ($departmentId, $request) {
                        return $query->where('department_id', $departmentId)
                            ->where('category_id', $request->category_id)
                            ->where('level', (int) ($request->level ?: 1))
                            ->where('to_status', $request->to_status);
                    }),
            ],
            'to_status' => ['required', new Enum(DocumentStatus::class)],
        ]);

        $department = Department::findOrFail($departmentId);

        $validated['department_id'] = $department->id;
        $validated['level'] = (int) ($validated['level'] ?? 1);
        WorkFlowRule::create($validated);

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
