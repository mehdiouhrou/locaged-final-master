<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    // List all categories
    public function index()
    {
        Gate::authorize('viewAny', Category::class);

        $categories = Category::with(['department'])
            ->withCount(['documents', 'subcategories'])
            ->get();

        return view('categories.index', compact('categories'));
    }

    // Show create form
    public function create()
    {
        Gate::authorize('create', Category::class);

        $user = auth()->user();

        $accessibleServiceIds = \App\Models\Box::getAccessibleServiceIds($user);

        if ($accessibleServiceIds === 'all' || $user->can('create category')) {
            $departments = \App\Models\Department::with('subDepartments.services')->get();
        } else {
            $departments = \App\Models\Department::with(['subDepartments' => function($subQuery) use ($accessibleServiceIds) {
                $subQuery->whereHas('services', function($serviceQuery) use ($accessibleServiceIds) {
                    $serviceQuery->whereIn('id', $accessibleServiceIds);
                })->with(['services' => function($serviceQuery) use ($accessibleServiceIds) {
                    $serviceQuery->whereIn('id', $accessibleServiceIds);
                }]);
            }])->whereHas('subDepartments.services', function($serviceQuery) use ($accessibleServiceIds) {
                $serviceQuery->whereIn('id', $accessibleServiceIds);
            })->get();
        }

        $allServices = $user->hasAnyRole(['master', 'IT Admin'])
            ? \App\Models\Service::with('subDepartment.department')->orderBy('name')->get()
            : collect();

        return view('categories.create', compact('departments', 'allServices'));
    }

    // Store a new category
    public function store(Request $request)
    {
        Gate::authorize('create', Category::class);

        $data = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'sub_department_id' => 'required|exists:sub_departments,id',
            'service_id' => 'required|exists:services,id',
            'category_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where(function ($query) use ($request) {
                    return $query->where('service_id', $request->input('service_id'));
                }),
            ],
            'shared_service_ids' => 'nullable|array',
            'shared_service_ids.*' => 'exists:services,id',
            'subcategories' => 'nullable|array',
            'subcategories.*' => 'required|string|max:255',
            'expiry_value' => 'required|integer|min:1',
            'expiry_unit' => 'required|string|in:days,months,years',
        ]);

        $category = Category::create([
            'name' => $data['category_name'],
            'department_id' => $data['department_id'],
            'sub_department_id' => $data['sub_department_id'],
            'service_id' => $data['service_id'],
            'expiry_value' => $data['expiry_value'] ?? null,
            'expiry_unit' => $data['expiry_unit'] ?? null,
        ]);

        // Sync services partagés (master et IT Admin uniquement)
        if (auth()->user()->hasAnyRole(['master', 'IT Admin'])) {
            $sharedIds = collect($request->input('shared_service_ids', []))
                ->filter(fn($id) => $id != $data['service_id'])
                ->values()
                ->all();
            $category->sharedServices()->sync($sharedIds);
        }

        // Créer les sous-catégories
        if ($request->has('subcategories') && !empty($request->get('subcategories'))) {
            foreach ($request->get('subcategories') as $subcategory) {
                if (!empty(trim($subcategory))) {
                    Subcategory::create([
                        'name' => $subcategory,
                        'category_id' => $category->id
                    ]);
                }
            }
        }

        return redirect()->route('categories.index')->with('success', 'Category created.');
    }

    // Show edit form
    public function edit(Category $category)
    {
        Gate::authorize('update', $category);

        $user = auth()->user();

        $accessibleServiceIds = \App\Models\Box::getAccessibleServiceIds($user);

        if ($accessibleServiceIds === 'all' || $user->can('create category')) {
            $departments = \App\Models\Department::with('subDepartments.services')->get();
        } else {
            $departments = \App\Models\Department::with(['subDepartments' => function($subQuery) use ($accessibleServiceIds) {
                $subQuery->whereHas('services', function($serviceQuery) use ($accessibleServiceIds) {
                    $serviceQuery->whereIn('id', $accessibleServiceIds);
                })->with(['services' => function($serviceQuery) use ($accessibleServiceIds) {
                    $serviceQuery->whereIn('id', $accessibleServiceIds);
                }]);
            }])->whereHas('subDepartments.services', function($serviceQuery) use ($accessibleServiceIds) {
                $serviceQuery->whereIn('id', $accessibleServiceIds);
            })->get();
        }

        $allServices = $user->hasAnyRole(['master', 'IT Admin'])
            ? \App\Models\Service::with('subDepartment.department')->orderBy('name')->get()
            : collect();

        $selectedSharedServiceIds = $user->hasAnyRole(['master', 'IT Admin'])
            ? $category->sharedServices->pluck('id')->toArray()
            : [];

        return view('categories.edit', compact('category', 'departments', 'allServices', 'selectedSharedServiceIds'));
    }

    // Update a category
    public function update(Request $request, Category $category)
    {
        Gate::authorize('update', $category);

        $data = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'sub_department_id' => 'required|exists:sub_departments,id',
            'service_id' => 'required|exists:services,id',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')
                    ->ignore($category->id)
                    ->where(function ($query) use ($request) {
                        return $query->where('service_id', $request->input('service_id'));
                    }),
            ],
            'shared_service_ids' => 'nullable|array',
            'shared_service_ids.*' => 'exists:services,id',
            'subcategories_id' => 'nullable|array',
            'subcategories_id.*' => 'nullable|integer|exists:subcategories,id',
            'subcategories_name' => 'nullable|array',
            'subcategories_name.*' => 'required|string|max:255',
            'expiry_value' => 'required|integer|min:1',
            'expiry_unit' => 'required|string|in:days,months,years',
        ]);

        $category->name = $data['name'];
        $category->department_id = $data['department_id'];
        $category->sub_department_id = $data['sub_department_id'];
        $category->service_id = $data['service_id'];
        $category->expiry_value = $data['expiry_value'] ?? null;
        $category->expiry_unit = $data['expiry_unit'] ?? null;
        $category->save();

        // Sync services partagés (master et IT Admin uniquement)
        if (auth()->user()->hasAnyRole(['master', 'IT Admin'])) {
            $sharedIds = collect($request->input('shared_service_ids', []))
                ->filter(fn($id) => $id != $data['service_id'])
                ->values()
                ->all();
            $category->sharedServices()->sync($sharedIds);
        }

        // Gérer les sous-catégories
        $submittedIds = collect($data['subcategories_id'] ?? []);
        $submittedNames = collect($data['subcategories_name'] ?? []);

        $existingSubIds = $category->subcategories()->pluck('id');
        $toDelete = $existingSubIds->diff($submittedIds->filter(fn($id) => $id !== null));
        $category->subcategories()->whereIn('id', $toDelete)->delete();

        foreach ($submittedNames->values() as $index => $name) {
            $id = $submittedIds[$index] ?? null;
            if ($id) {
                $subcategory = $category->subcategories()->find($id);
                if ($subcategory) {
                    $subcategory->update(['name' => $name]);
                }
            } else {
                $category->subcategories()->create(['name' => $name]);
            }
        }

        return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
    }

    // Delete a category
    public function destroy(Category $category)
    {
        Gate::authorize('delete', $category);
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'Category deleted.');
    }

    // Show subcategories for a category
    public function subcategories(Category $category)
    {
        Gate::authorize('view', $category);
        $subcategories = $category->subcategories()->withCount('documents')->get();
        return view('categories.subcategories', compact('category', 'subcategories'));
    }
}