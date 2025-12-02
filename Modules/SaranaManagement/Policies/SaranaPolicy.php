<?php

namespace Modules\SaranaManagement\Policies;

use App\Models\User;
use Modules\SaranaManagement\Entities\Sarana;

class SaranaPolicy
{
    /**
     * Check if user can manage sarpras (sarana management)
     */
    protected function canManageSarpras(User $user): bool
    {
        return $user->hasPermissionTo('sarpras.manage');
    }

    /**
     * Determine if the user can view any saranas
     */
    public function viewAny(User $user): bool
    {
        return $this->canManageSarpras($user);
    }

    /**
     * Determine if the user can view the sarana
     */
    public function view(User $user, Sarana $sarana): bool
    {
        return $this->canManageSarpras($user);
    }

    /**
     * Determine if the user can create saranas
     */
    public function create(User $user): bool
    {
        return $this->canManageSarpras($user);
    }

    /**
     * Determine if the user can update the sarana
     */
    public function update(User $user, Sarana $sarana): bool
    {
        return $this->canManageSarpras($user);
    }

    /**
     * Determine if the user can delete the sarana
     */
    public function delete(User $user, Sarana $sarana): bool
    {
        return $this->canManageSarpras($user);
    }

    /**
     * Determine if the user can manage units for the sarana
     */
    public function manageUnits(User $user, Sarana $sarana): bool
    {
        return $this->canManageSarpras($user) && $user->hasPermissionTo('sarpras.unit_manage');
    }
}
