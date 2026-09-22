<?php

namespace App\Policies;

use App\Models\LoanRequest;
use App\Models\User;

class LoanRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view any loan request')
            || $user->can('view department loan request')
            || $user->can('view own loan request');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LoanRequest $loanRequest): bool
    {
        if ($user->can('view any loan request')) {
            return true;
        }

        if ($user->id === $loanRequest->requested_by) {
            return true;
        }

        if ($user->can('view department loan request')) {
            $departmentId = $loanRequest->resolveDepartmentId();

            if ($departmentId !== null && $user->departments->pluck('id')->contains($departmentId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create loan request');
    }

    /**
     * Determine whether the user can update the model.
     * Only the requester may edit their own request, and only while still pending.
     */
    public function update(User $user, LoanRequest $loanRequest): bool
    {
        if ($user->cannot('update loan request')) {
            return false;
        }

        return $user->id === $loanRequest->requested_by && $loanRequest->status === 'requested';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LoanRequest $loanRequest): bool
    {
        if ($user->cannot('delete loan request')) {
            return false;
        }

        if ($user->can('view any loan request')) {
            return true;
        }

        return $user->id === $loanRequest->requested_by && $loanRequest->status === 'requested';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, LoanRequest $loanRequest): bool
    {
        return $user->can('restore loan request');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, LoanRequest $loanRequest): bool
    {
        return $user->can('forceDelete loan request');
    }

    /**
     * Determine whether the user can approve a loan request.
     */
    public function approve(User $user, LoanRequest $loanRequest): bool
    {
        if ($user->cannot('approve loan request')) {
            return false;
        }

        return $loanRequest->status === 'requested';
    }

    /**
     * Determine whether the user can decline a loan request.
     */
    public function decline(User $user, LoanRequest $loanRequest): bool
    {
        if ($user->cannot('decline loan request')) {
            return false;
        }

        return $loanRequest->status === 'requested';
    }

    /**
     * Determine whether the user can process (mark picked up / returned) a loan request.
     */
    public function process(User $user, LoanRequest $loanRequest): bool
    {
        if ($user->cannot('process loan request')) {
            return false;
        }

        return in_array($loanRequest->status, ['approved', 'picked_up'], true);
    }
}
