<?php

namespace App\View\Composers;

use App\Http\Controllers\HomeController;
use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\Service;
use App\Models\SubDepartment;
use App\Models\User;
use App\Services\DashboardActivityFeedService;
use App\Services\ProfileCategoryAccessService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SidebarComposer
{
    public function compose(View $view): void
    {
        $user = Auth::user();
        $favoritesPayload = [
            'categories' => [],
            'documents' => [],
            'recent' => [],
        ];
        $myCategoriesPayload = [
            'items' => [],
            'total' => 0,
            'favorites' => [],
        ];

        if (! $user) {
            $view->with('sidebarCategoryTree', []);
            $view->with('sidebarFavorites', $favoritesPayload);
            $view->with('sidebarMyCategories', $myCategoriesPayload);
            $view->with('sidebarMyTasksCount', 0);

            return;
        }

        $view->with('sidebarMyTasksCount', $this->buildSidebarMyTasksCount($user));

        if (! $user->can('viewAny', Document::class)) {
            $view->with('sidebarCategoryTree', []);
            $view->with('sidebarFavorites', $favoritesPayload);
            $view->with('sidebarMyCategories', $this->buildSidebarMyCategories($user));

            return;
        }

        /** @var HomeController $home */
        $home = app(HomeController::class);
        $base = $home->getVisibleDocumentsQuery();

        $isMaster = $user->can('view any role');
        $isSuperAdmin = $user->can('view organization wide reports');

        if ($isMaster || $isSuperAdmin || $user->can('view any department')) {
            $departments = Department::query()->orderBy('name')->get();
        } else {
            $departments = $user->departments()->orderBy('name')->get();
        }

        $tree = [];
        foreach ($departments as $d) {
            $count = (clone $base)->where('department_id', $d->id)->count();
            $children = [];

            if ($isMaster || $isSuperAdmin || $user->can('view any department') || $user->can('view department document')) {
                $subs = SubDepartment::query()->where('department_id', $d->id)->orderBy('name')->get();
                foreach ($subs as $sub) {
                    $serviceIds = Service::query()->where('sub_department_id', $sub->id)->pluck('id');
                    $subCount = $serviceIds->isEmpty()
                        ? 0
                        : (clone $base)->whereIn('service_id', $serviceIds)->count();
                    $children[] = [
                        'id' => $sub->id,
                        'type' => 'subdepartment',
                        'name' => $sub->name,
                        'count' => $subCount,
                    ];
                }
            }

            $tree[] = [
                'id' => $d->id,
                'type' => 'department',
                'name' => $d->name,
                'count' => $count,
                'children' => $children,
            ];
        }

        $view->with('sidebarCategoryTree', $tree);

        $favoriteDocuments = (clone $base)
            ->with(['latestVersion', 'category'])
            ->whereHas('favoritedByUsers', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
            ->latest('documents.updated_at')
            ->limit(5)
            ->get();

        $favoriteCategories = $favoriteDocuments
            ->filter(fn ($doc) => $doc->category_id && $doc->category)
            ->groupBy('category_id')
            ->map(function ($docs) {
                $first = $docs->first();

                return [
                    'id' => $first->category_id,
                    'name' => $first->category?->name ?? __('Category'),
                    'count' => $docs->count(),
                ];
            })
            ->sortByDesc('count')
            ->take(5)
            ->values()
            ->all();

        $sidebarActivityFeed = app(DashboardActivityFeedService::class)
            ->feed($home->getVisibleDocumentsQuery(), $user, 5)
            ->all();

        $view->with('sidebarFavorites', [
            'categories' => $favoriteCategories,
            'documents' => $favoriteDocuments->values()->all(),
            'recent' => $sidebarActivityFeed,
        ]);

        $view->with('sidebarMyCategories', $this->buildSidebarMyCategories($user, $favoriteCategories));
    }

    /**
     * Catégories « Mes catégories » (consultation / profil), indépendamment du droit de gestion CRUD.
     *
     * @param  array<int, array{id: int, name: string, count?: int}>  $favoriteCategoriesFromDocs
     * @return array{items: list<array{id: int, name: string}>, total: int, favorites: list<array{id: int, name: string}>}
     */
    private function buildSidebarMyCategories(User $user, array $favoriteCategoriesFromDocs = []): array
    {
        // Bypass : accès total
        $isBypass = $user->can('view any document')
            || $user->can('create category')
            || $user->can('update category')
            || $user->can('delete category');

        $accessibleCategoriesQuery = Category::withoutGlobalScopes()->orderBy('name');

        if (! $isBypass) {
            $directIds = \DB::table('user_category_access')
                ->where('user_id', $user->id)
                ->pluck('category_id');

            $subCatParentIds = \DB::table('user_subcategory_access')
                ->join('subcategories', 'subcategories.id', '=', 'user_subcategory_access.subcategory_id')
                ->where('user_subcategory_access.user_id', $user->id)
                ->pluck('subcategories.category_id');

            $allIds = $directIds->merge($subCatParentIds)->unique()->filter()->values();

            if ($allIds->isEmpty()) {
                $accessibleCategoriesQuery->whereRaw('1 = 0');
            } else {
                $accessibleCategoriesQuery->whereIn('id', $allIds->all());
            }
        }

        $accessibleCategories = $accessibleCategoriesQuery
            ->get(['id', 'name'])
            ->map(fn ($cat) => ['id' => $cat->id, 'name' => $cat->name])
            ->values();

        $favoriteCategoryIds = collect($favoriteCategoriesFromDocs)->pluck('id')->filter()->values();
        $favoriteAccessibleCategories = $accessibleCategories
            ->whereIn('id', $favoriteCategoryIds)
            ->take(5)
            ->values()
            ->all();

        return [
            'items' => $accessibleCategories->take(5)->all(),
            'total' => $accessibleCategories->count(),
            'favorites' => $favoriteAccessibleCategories,
        ];
    }

    /**
     * Nombre total d'éléments dans "Mes tâches" (à approuver + relectures + mes documents actifs).
     */
    private function buildSidebarMyTasksCount(User $user): int
    {
        $toApproveCount = 0;
        if ($user->can('approve', Document::class) || $user->can('decline', Document::class)) {
            $toApproveCount = app(HomeController::class)->getVisibleDocumentsQuery()
                ->where('status', 'pending')
                ->count();
        }

        $myReviewsCount = Document::whereHas('reviewers', function ($q) use ($user) {
            $q->where('reviewer_id', $user->id)->where('status', 'pending');
        })->where('status', 'en_relecture')->count();

        $myDocumentsCount = Document::where('created_by', $user->id)
            ->whereIn('status', ['brouillon', 'en_relecture', 'valide'])
            ->count();

        return $toApproveCount + $myReviewsCount + $myDocumentsCount;
    }
}
