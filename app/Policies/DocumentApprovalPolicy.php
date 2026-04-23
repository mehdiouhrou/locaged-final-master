<?php

namespace App\Policies;

use App\Models\DocumentApproval;
use App\Models\User;

class DocumentApprovalPolicy
{
    /**
     * Determine if the user can view any document approvals.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view any document') 
            || $user->can('approve document') 
            || $user->can('decline document');
    }

    /**
     * Determine if the user can view a specific document approval.
     */
    public function view(User $user, DocumentApproval $approval): bool
    {
        // Users can view approvals for documents they can see
        return $user->can('view', $approval->document);
    }

    /**
     * Determine if the user can approve this specific approval level.
     */
    public function approve(User $user, DocumentApproval $approval): bool
    {
        if ($approval->status !== 'pending') {
            return false;
        }

        if (!$user->can('approve document')) {
            return false;
        }

        // Check if user is in the list of eligible approvers for this level
        $approvers = $approval->workflowRule->getApprovers();
        return $approvers->pluck('id')->contains($user->id);
    }

    /**
     * Determine if the user can decline this specific approval level.
     */
    public function decline(User $user, DocumentApproval $approval): bool
    {
        if ($approval->status !== 'pending') {
            return false;
        }

        if (!$user->can('decline document')) {
            return false;
        }

        // Check if user is in the list of eligible approvers for this level
        $approvers = $approval->workflowRule->getApprovers();
        return $approvers->pluck('id')->contains($user->id);
    }
}
