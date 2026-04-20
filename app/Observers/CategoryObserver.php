<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\AuditService;

class CategoryObserver
{
    public function created(Category $category): void
    {
        AuditService::logCategoryAudit('category_created', [
            'category_id' => $category->id,
            'name' => $category->name,
            'expiry_value' => $category->expiry_value,
            'expiry_unit' => $category->expiry_unit,
            'created_by' => auth()->id(),
        ]);
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

        AuditService::logCategoryAudit('category_updated', [
            'category_id' => $category->id,
            'before' => $before,
            'after' => $changes,
        ]);
    }

    public function deleted(Category $category): void
    {
        AuditService::logCategoryAudit('category_deleted', [
            'category_id' => $category->id,
            'name' => $category->name,
            'expiry_value' => $category->expiry_value,
            'expiry_unit' => $category->expiry_unit,
        ]);
    }
}
