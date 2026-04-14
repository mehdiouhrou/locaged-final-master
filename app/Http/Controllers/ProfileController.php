<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Department;
use App\Models\Profile;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class ProfileController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Profile::class);

        $profiles = Profile::query()
            ->withCount(['categories', 'users', 'services', 'roles', 'departments'])
            ->orderBy('name')
            ->paginate(20);

        return view('access-profiles.index', compact('profiles'));
    }

    public function show(Profile $profile): View
    {
        Gate::authorize('view', $profile);

        $profile->loadCount(['categories', 'users', 'services', 'roles', 'departments']);
        $profile->load([
            'categories:id,name',
            'users:id,full_name,email',
            'services:id,name,sub_department_id',
            'roles:id,name',
            'departments:id,name',
            'creator:id,full_name,email',
        ]);

        return view('access-profiles.show', compact('profile'));
    }

    public function create(): View
    {
        Gate::authorize('create', Profile::class);

        return view('access-profiles.create', $this->formContext());
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Profile::class);

        $data = $this->validated($request);

        $profile = Profile::query()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'created_by' => auth()->id(),
        ]);

        $this->syncRelations($profile, $data);

        return redirect()->route('access-profiles.index')
            ->with('success', __('Profil d’accès créé.'));
    }

    public function edit(Profile $profile): View
    {
        Gate::authorize('update', $profile);

        $profile->load(['categories', 'users', 'services', 'roles', 'departments']);

        return view('access-profiles.edit', array_merge(
            $this->formContext(),
            ['profile' => $profile]
        ));
    }

    public function update(Request $request, Profile $profile): RedirectResponse
    {
        Gate::authorize('update', $profile);

        $data = $this->validated($request);

        $profile->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        $this->syncRelations($profile, $data);

        return redirect()->route('access-profiles.index')
            ->with('success', __('Profil d’accès mis à jour.'));
    }

    public function destroy(Profile $profile): RedirectResponse
    {
        Gate::authorize('delete', $profile);

        $profile->delete();

        return redirect()->route('access-profiles.index')
            ->with('success', __('Profil d’accès supprimé.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formContext(): array
    {
        $categories = Category::query()
            ->withoutGlobalScopes()
            ->orderBy('name')
            ->get(['id', 'name']);

        $users = User::query()
            ->orderBy('full_name')
            ->orderBy('email')
            ->get(['id', 'full_name', 'email']);

        $services = Service::query()
            ->with('subDepartment.department')
            ->orderBy('name')
            ->get();

        $roles = Role::query()->orderBy('name')->get(['id', 'name']);

        $departments = Department::query()
            ->withoutGlobalScopes()
            ->orderBy('name')
            ->get(['id', 'name']);

        return compact('categories', 'users', 'services', 'roles', 'departments');
    }

    /**
     * @return array{name: string, description: ?string, category_ids: array, user_ids: array, service_ids: array, role_ids: array, department_ids: array}
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'integer|exists:services,id',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'integer|exists:roles,id',
            'department_ids' => 'nullable|array',
            'department_ids.*' => 'integer|exists:departments,id',
        ]);
    }

    /**
     * @param  array{name: string, description: ?string, category_ids: array, user_ids: array, service_ids: array, role_ids: array, department_ids: array}  $data
     */
    private function syncRelations(Profile $profile, array $data): void
    {
        $profile->categories()->sync($data['category_ids'] ?? []);
        $profile->users()->sync($data['user_ids'] ?? []);
        $profile->services()->sync($data['service_ids'] ?? []);
        $profile->roles()->sync($data['role_ids'] ?? []);
        $profile->departments()->sync($data['department_ids'] ?? []);
    }
}
