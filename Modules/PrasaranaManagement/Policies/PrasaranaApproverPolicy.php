<?php

namespace Modules\PrasaranaManagement\Policies;

use App\Models\User;
use Modules\PrasaranaManagement\Entities\Prasarana;
use Modules\PrasaranaManagement\Entities\PrasaranaApprover;

class PrasaranaApproverPolicy
{
    /**
     * Check if user can manage prasarana approvers
     */
    protected function canManageApprovers(User $user): bool
    {
        // Permission khusus untuk mengelola approver prasarana
        return $user->hasPermissionTo('sarpras.assign_specific_approver');
    }

    /**
     * Determine if the user can view any approvers for a prasarana
     */
    public function viewAny(User $user, Prasarana $prasarana): bool
    {
        return $this->canManageApprovers($user);
    }

    /**
     * Determine if the user can create approver for a prasarana
     */
    public function create(User $user, Prasarana $prasarana): bool
    {
        return $this->canManageApprovers($user);
    }

    /**
     * Determine if the user can update an approver mapping
     */
    public function update(User $user, PrasaranaApprover $approver): bool
    {
        return $this->canManageApprovers($user);
    }

    /**
     * Determine if the user can delete an approver mapping
     */
    public function delete(User $user, PrasaranaApprover $approver): bool
    {
        return $this->canManageApprovers($user);
    }
}
