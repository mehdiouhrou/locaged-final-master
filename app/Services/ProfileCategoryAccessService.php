<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Résolution des catégories visibles pour un utilisateur via profils V2.
 *
 * @see docs/ADR-001-locaged-three-pillars.md
 * @see \App\Models\Profile::getAccessibleCategoryIds()
 */
class ProfileCategoryAccessService
{
    /**
     * Catégories accessibles via profils (user direct + services de l'utilisateur).
     * null = pas de filtre (voir toutes les catégories).
     *
     * @return Collection<int, int>|null liste d'IDs catégorie, ou null si accès total
     */
    public function accessibleCategoryIdsFor(User $user): ?Collection
    {
        return Cache::remember(
            $this->cacheKey($user),
            now()->addMinutes(5),
            function () use ($user) {
                if ($user->can('view any document')
                    || $user->can('create category')
                    || $user->can('update category')
                    || $user->can('delete category')) {
                    return null;
                }

                $profileIds = collect();
                if (Schema::hasTable('profile_user')) {
                    $profileIds = $profileIds->merge(
                        DB::table('profile_user')
                            ->where('user_id', $user->id)
                            ->pluck('profile_id')
                    );
                }

                $serviceIds = $this->resolveServiceIds($user);
                if ($serviceIds->isNotEmpty() && Schema::hasTable('profile_service')) {
                    $profileIds = $profileIds->merge(
                        DB::table('profile_service')
                            ->whereIn('service_id', $serviceIds)
                            ->pluck('profile_id')
                    );
                }

                $departmentIds = $this->resolveDepartmentIds($user, $serviceIds);
                if ($departmentIds->isNotEmpty() && Schema::hasTable('profile_department')) {
                    $profileIds = $profileIds->merge(
                        DB::table('profile_department')
                            ->whereIn('department_id', $departmentIds)
                            ->pluck('profile_id')
                    );
                }

                if (Schema::hasTable('profile_role') && Schema::hasTable('model_has_roles')) {
                    $roleIds = DB::table('model_has_roles')
                        ->where('model_type', User::class)
                        ->where('model_id', $user->id)
                        ->pluck('role_id');

                    if ($roleIds->isNotEmpty()) {
                        $profileIds = $profileIds->merge(
                            DB::table('profile_role')
                                ->whereIn('role_id', $roleIds)
                                ->pluck('profile_id')
                        );
                    }
                }

                $profileIds = $profileIds->unique()->filter()->values();

                $categoryIds = collect();

                if ($profileIds->isNotEmpty() && Schema::hasTable('profile_category')) {
                    $categoryIds = $categoryIds->merge(
                        DB::table('profile_category')
                            ->whereIn('profile_id', $profileIds)
                            ->pluck('category_id')
                    );
                }

                if ($serviceIds->isNotEmpty() && Schema::hasTable('category_service')) {
                    $categoryIds = $categoryIds->merge(
                        DB::table('category_service')
                            ->whereIn('service_id', $serviceIds)
                            ->pluck('category_id')
                    );
                }

                if (Schema::hasTable('category_role') && Schema::hasTable('model_has_roles')) {
                    $roleIds = DB::table('model_has_roles')
                        ->where('model_type', User::class)
                        ->where('model_id', $user->id)
                        ->pluck('role_id');

                    if ($roleIds->isNotEmpty()) {
                        $categoryIds = $categoryIds->merge(
                            DB::table('category_role')
                                ->whereIn('role_id', $roleIds)
                                ->pluck('category_id')
                        );
                    }
                }

                return $categoryIds->unique()->filter()->values();
            }
        );
    }

    private function cacheKey(User $user): string
    {
        return 'profile_category_access:user:'.$user->id;
    }

    private function resolveServiceIds(User $user): Collection
    {
        $serviceIds = collect();

        if ($user->service_id) {
            $serviceIds->push($user->service_id);
        }
        if (method_exists($user, 'services')) {
            $serviceIds = $serviceIds->merge($user->services()->pluck('services.id'));
        }

        return $serviceIds->unique()->filter()->values();
    }

    private function resolveDepartmentIds(User $user, Collection $serviceIds): Collection
    {
        $departmentIds = collect();

        if (Schema::hasTable('department_user')) {
            $departmentIds = $departmentIds->merge(
                DB::table('department_user')
                    ->where('user_id', $user->id)
                    ->pluck('department_id')
            );
        }

        if ($serviceIds->isNotEmpty() && Schema::hasTable('services') && Schema::hasTable('sub_departments')) {
            $departmentIds = $departmentIds->merge(
                DB::table('services')
                    ->join('sub_departments', 'sub_departments.id', '=', 'services.sub_department_id')
                    ->whereIn('services.id', $serviceIds)
                    ->pluck('sub_departments.department_id')
            );
        }

        return $departmentIds->unique()->filter()->values();
    }
}
