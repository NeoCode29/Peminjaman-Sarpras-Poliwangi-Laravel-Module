<?php

namespace Modules\SaranaManagement\Policies;

use App\Models\User;
use Modules\SaranaManagement\Entities\Sarana;
use Modules\SaranaManagement\Entities\SaranaApprover;

class SaranaApproverPolicy
{
    /**
     * Check if user can manage sarana approvers
     */
    protected function canManageApprovers(User $user): bool
    {
        // Permission khusus untuk mengelola approver sarana
        return $user->hasPermissionTo('sarpras.assign_specific_approver');
    }

    /**
     * Determine if the user can view any approvers for a sarana
     */
    public function viewAny(User $user, Sarana $sarana): bool
    {
        return $this->canManageApprovers($user);
    }

    /**
     * Determine if the user can create approver for a sarana
     */
    public function create(User $user, Sarana $sarana): bool
    {
        return $this->canManageApprovers($user);
    }

    /**
     * Determine if the user can update an approver mapping
     */
    public function update(User $user, SaranaApprover $approver): bool
    {
        return $this->canManageApprovers($user);
    }

    /**
     * Determine if the user can delete an approver mapping
     */
    public function delete(User $user, SaranaApprover $approver): bool
    {
        return $this->canManageApprovers($user);
    }
}
