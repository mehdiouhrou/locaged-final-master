<?php

namespace App\View\Composers;

use App\Http\Controllers\HomeController;
use App\Models\Category;
use App\Models\Department;
use App\Models\Service;
use App\Models\SubDepartment;
use App\Services\DashboardActivityFeedService;
use App\Services\ProfileCategoryAccessService;
use Illuminate\Support\Facades\Auth;
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

        if (! $user || ! $user->can('viewAny', \App\Models\Document::class)) {
            $view->with('sidebarCategoryTree', []);
            $view->with('sidebarFavorites', $favoritesPayload);
            $view->with('sidebarMyCategories', $myCategoriesPayload);

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
            ->feed($home->getVisibleDocumentsQuery(), $user, 6)
            ->all();

        $view->with('sidebarFavorites', [
            'categories' => $favoriteCategories,
            'documents' => $favoriteDocuments->values()->all(),
            'recent' => $sidebarActivityFeed,
        ]);

        $accessibleCategoryIds = app(ProfileCategoryAccessService::class)->accessibleCategoryIdsFor($user);
        $accessibleCategoriesQuery = Category::query()->orderBy('name');
        if ($accessibleCategoryIds !== null) {
            if ($accessibleCategoryIds->isEmpty()) {
                $accessibleCategoriesQuery->whereRaw('1 = 0');
            } else {
                $accessibleCategoriesQuery->whereIn('id', $accessibleCategoryIds->all());
            }
        }

        $accessibleCategories = $accessibleCategoriesQuery
            ->get(['id', 'name'])
            ->map(fn ($cat) => ['id' => $cat->id, 'name' => $cat->name])
            ->values();

        $favoriteCategoryIds = collect($favoriteCategories)->pluck('id')->filter()->values();
        $favoriteAccessibleCategories = $accessibleCategories
            ->whereIn('id', $favoriteCategoryIds)
            ->take(5)
            ->values()
            ->all();

        $view->with('sidebarMyCategories', [
            'items' => $accessibleCategories->take(5)->all(),
            'total' => $accessibleCategories->count(),
            'favorites' => $favoriteAccessibleCategories,
        ]);
    }
}
