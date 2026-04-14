<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Profile;
use App\Models\Service;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class CategoryController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Category::class);

        $categories = Category::query()
            ->withCount(['documents', 'subcategories'])
            ->orderBy('name')
            ->get();

        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        Gate::authorize('create', Category::class);

        $profiles = Profile::query()->orderBy('name')->get(['id', 'name']);
        $services = Service::query()->with('subDepartment.department')->orderBy('name')->get();
        $roles = Role::query()->orderBy('name')->get(['id', 'name']);

        return view('categories.create', compact('profiles', 'services', 'roles'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Category::class);

        $data = $request->validate([
            'category_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name'),
            ],
            'subcategories' => 'nullable|array',
            'subcategories.*' => 'required|string|max:255',
            'expiry_value' => 'required|integer|min:1',
            'expiry_unit' => 'required|string|in:days,months,years',
            'profile_ids' => 'nullable|array',
            'profile_ids.*' => 'integer|exists:profiles,id',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'integer|exists:services,id',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'integer|exists:roles,id',
        ]);

        $category = Category::create([
            'name' => $data['category_name'],
            'expiry_value' => $data['expiry_value'] ?? null,
            'expiry_unit' => $data['expiry_unit'] ?? null,
        ]);

        $category->profiles()->sync($data['profile_ids'] ?? []);
        $category->services()->sync($data['service_ids'] ?? []);
        $category->roles()->sync($data['role_ids'] ?? []);

        if ($request->has('subcategories') && ! empty($request->get('subcategories'))) {
            foreach ($request->get('subcategories') as $subcategory) {
                if (! empty(trim($subcategory))) {
                    Subcategory::create([
                        'name' => $subcategory,
                        'category_id' => $category->id,
                    ]);
                }
            }
        }

        return redirect()->route('categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category)
    {
        Gate::authorize('update', $category);

        $category->load('profiles:id', 'services:id', 'roles:id');
        $profiles = Profile::query()->orderBy('name')->get(['id', 'name']);
        $services = Service::query()->with('subDepartment.department')->orderBy('name')->get();
        $roles = Role::query()->orderBy('name')->get(['id', 'name']);

        return view('categories.edit', compact('category', 'profiles', 'services', 'roles'));
    }

    public function update(Request $request, Category $category)
    {
        Gate::authorize('update', $category);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($category->id),
            ],
            'subcategories_id' => 'nullable|array',
            'subcategories_id.*' => 'nullable|integer|exists:subcategories,id',
            'subcategories_name' => 'nullable|array',
            'subcategories_name.*' => 'required|string|max:255',
            'expiry_value' => 'required|integer|min:1',
            'expiry_unit' => 'required|string|in:days,months,years',
            'profile_ids' => 'nullable|array',
            'profile_ids.*' => 'integer|exists:profiles,id',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'integer|exists:services,id',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'integer|exists:roles,id',
        ]);

        $category->name = $data['name'];
        $category->expiry_value = $data['expiry_value'] ?? null;
        $category->expiry_unit = $data['expiry_unit'] ?? null;
        $category->save();

        $submittedIds = collect($data['subcategories_id'] ?? []);
        $submittedNames = collect($data['subcategories_name'] ?? []);

        $existingSubIds = $category->subcategories()->pluck('id');

        $toDelete = $existingSubIds->diff($submittedIds->filter(fn ($id) => $id !== null));
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

        $category->profiles()->sync($data['profile_ids'] ?? []);
        $category->services()->sync($data['service_ids'] ?? []);
        $category->roles()->sync($data['role_ids'] ?? []);

        return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        Gate::authorize('delete', $category);

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Category deleted.');
    }

    public function subcategories(Category $category)
    {
        Gate::authorize('view', $category);

        $subcategories = $category->subcategories()->withCount('documents')->get();

        return view('categories.subcategories', compact('category', 'subcategories'));
    }
}
