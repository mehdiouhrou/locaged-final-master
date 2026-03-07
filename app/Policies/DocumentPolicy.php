<?php
namespace App\Policies;
use App\Models\Document;
use App\Models\Service;
use App\Models\SubDepartment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view any document')
            || $user->can('view department document')
            || $user->can('view service document')
            || $user->can('view own document');
    }

    public function view(User $user, Document $document): bool
    {
        if ($user->can('view any document')) {
            return true;
        }

        if ($user->can('view service document')) {
            $visibleServiceIds = collect();
            if ($user->relationLoaded('services') || method_exists($user, 'services')) {
                $visibleServiceIds = $visibleServiceIds->merge($user->services->pluck('id'));
            }
            $subDeptIds = collect();
            if ($user->relationLoaded('subDepartments') || method_exists($user, 'subDepartments')) {
                $subDeptIds = $subDeptIds->merge($user->subDepartments->pluck('id'));
            }
            $subDeptIds = $subDeptIds->unique()->filter();
            if ($subDeptIds->isNotEmpty()) {
                $visibleServiceIds = $visibleServiceIds->merge(
                    Service::whereIn('sub_department_id', $subDeptIds)->pluck('id')
                );
            }
            $visibleServiceIds = $visibleServiceIds->unique()->filter();

            
            if ($document->service_id && $visibleServiceIds->contains($document->service_id)) {
                return true;
            }

            if ($visibleServiceIds->isNotEmpty() && $document->category_id) {
                $sharedCategoryIds = DB::table('category_service')
                    ->whereIn('service_id', $visibleServiceIds->all())
                    ->pluck('category_id');

                

                if ($sharedCategoryIds->contains($document->category_id)) {
                    return true;
                }
            } else {
               
            }
        }

        if ($user->can('view department document')
            && $user->departments->pluck('id')->contains($document->department_id)) {
            return true;
        }

        if ($user->can('view own document') && $user->id === $document->created_by) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('create document');
    }

    public function update(User $user, Document $document): bool
    {
        if ($user->cannot('update document')) {
            return false;
        }
        if ($user->can('view any document')) {
            return true;
        }
        if ($user->can('view service document')) {
            $visibleServiceIds = collect();
            if ($user->relationLoaded('services') || method_exists($user, 'services')) {
                $visibleServiceIds = $visibleServiceIds->merge($user->services->pluck('id'));
            }
            $subDeptIds = collect();
            if ($user->relationLoaded('subDepartments') || method_exists($user, 'subDepartments')) {
                $subDeptIds = $subDeptIds->merge($user->subDepartments->pluck('id'));
            }
            $subDeptIds = $subDeptIds->unique()->filter();
            if ($subDeptIds->isNotEmpty()) {
                $visibleServiceIds = $visibleServiceIds->merge(
                    Service::whereIn('sub_department_id', $subDeptIds)->pluck('id')
                );
            }
            $visibleServiceIds = $visibleServiceIds->unique()->filter();
            if ($document->service_id && $visibleServiceIds->contains($document->service_id)) {
                return true;
            }
        }
        if ($user->can('view department document')
            && $user->departments->pluck('id')->contains($document->department_id)) {
            return true;
        }
        if ($user->can('view own document') && $user->id === $document->created_by) {
            return true;
        }
        return false;
    }

    public function delete(User $user, Document $document): bool
    {
        if ($user->cannot('delete document')) {
            return false;
        }
        if ($user->can('view any document')) {
            return true;
        }
        if ($user->can('view department document')
            && $user->departments->pluck('id')->contains($document->department_id)) {
            return true;
        }
        if ($user->can('view own document') && $user->id === $document->created_by) {
            return true;
        }
        return false;
    }

    public function restore(User $user, Document $document): bool
    {
        if ($user->cannot('restore document')) {
            return false;
        }
        if ($user->can('view any document')) {
            return true;
        }
        if ($user->can('view department document')
            && $user->departments->pluck('id')->contains($document->department_id)) {
            return true;
        }
        if ($user->can('view own document') && $user->id === $document->created_by) {
            return true;
        }
        return false;
    }

    public function forceDelete(User $user, Document $document): bool
    {
        if ($user->cannot('forceDelete document')) {
            return false;
        }
        if ($user->can('view any document')) {
            return true;
        }
        if ($user->can('view department document')
            && $user->departments->pluck('id')->contains($document->department_id)) {
            return true;
        }
        if ($user->can('view own document') && $user->id === $document->created_by) {
            return true;
        }
        return false;
    }

    public function approve(User $user): bool
    {
        return $user->can('approve document');
    }

    public function decline(User $user): bool
    {
        return $user->can('decline document');
    }

    public function permanentDelete(User $user, Document $document): bool
    {
        if ($user->can('forceDelete document')) {
            return true;
        }
        if ($document->status === 'declined' && $document->created_by === $user->id) {
            return true;
        }
        return false;
    }
}