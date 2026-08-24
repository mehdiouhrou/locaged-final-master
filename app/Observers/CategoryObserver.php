<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\AuditService;

class CategoryObserver
{
    public function created(Category $category): void
    {
        AuditService::logSubject(
            action: 'category_created',
            subjectType: 'category',
            subjectId: $category->id,
            metadata: [
                'category_id' => $category->id,
                'name' => $category->name,
                'expiry_value' => $category->expiry_value,
                'expiry_unit' => $category->expiry_unit,
                'created_by' => auth()->id(),
            ],
        );
    }

    public function updated(Category $category): void
    {
        $changes = $category->getChanges();
        unset($changes['updated_at']);

        if ($changes === []) {
            return;
        }

        $before = [];
        foreach (array_keys($changes) as $attr) {
            $before[$attr] = $category->getOriginal($attr);
        }

        $diff = AuditService::diff($before, $changes, array_keys($changes));

        AuditService::logSubject(
            action: 'category_updated',
            subjectType: 'category',
            subjectId: $category->id,
            metadata: [
                'category_id' => $category->id,
                'name' => $category->name,
                'changes' => $diff,
            ],
        );
    }

    public function deleted(Category $category): void
    {
        AuditService::logSubject(
            action: 'category_deleted',
            subjectType: 'category',
            subjectId: $category->id,
            metadata: [
                'category_id' => $category->id,
                'name' => $category->name,
                'expiry_value' => $category->expiry_value,
                'expiry_unit' => $category->expiry_unit,
            ],
        );
    }
}
