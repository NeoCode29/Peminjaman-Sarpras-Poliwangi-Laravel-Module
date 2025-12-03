<?php

namespace App\Policies;

use App\Models\GlobalApprover;
use App\Models\User;

class GlobalApproverPolicy
{
    /**
     * Helper untuk cek apakah user memiliki permission manage global approver.
     * Permission ini diperlukan untuk akses halaman manajemen global approver.
     */
    protected function canManageGlobalApprovers(User $user): bool
    {
        return $user->hasPermissionTo('global_approver.manage');
    }

    /**
     * Determine whether the user can view any global approvers.
     */
    public function viewAny(User $user): bool
    {
        return $this->canManageGlobalApprovers($user);
    }

    /**
     * Determine whether the user can view the global approver.
     */
    public function view(User $user, GlobalApprover $globalApprover): bool
    {
        return $this->canManageGlobalApprovers($user);
    }

    /**
     * Determine whether the user can create global approvers.
     */
    public function create(User $user): bool
    {
        return $this->canManageGlobalApprovers($user);
    }

    /**
     * Determine whether the user can update the global approver.
     */
    public function update(User $user, GlobalApprover $globalApprover): bool
    {
        return $this->canManageGlobalApprovers($user);
    }

    /**
     * Determine whether the user can delete the global approver.
     */
    public function delete(User $user, GlobalApprover $globalApprover): bool
    {
        return $this->canManageGlobalApprovers($user);
    }

    /**
     * Determine whether the user can toggle the global approver status.
     */
    public function toggleStatus(User $user, GlobalApprover $globalApprover): bool
    {
        return $this->canManageGlobalApprovers($user);
    }
}
