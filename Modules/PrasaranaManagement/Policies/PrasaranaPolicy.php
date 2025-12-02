<?php

namespace Modules\PrasaranaManagement\Policies;

use App\Models\User;
use Modules\PrasaranaManagement\Entities\Prasarana;

class PrasaranaPolicy
{
    /**
     * Check if user can manage prasarana (sarpras)
     */
    protected function canManageSarpras(User $user): bool
    {
        return $user->hasPermissionTo('sarpras.manage');
    }

    /**
     * Determine if the user can view any prasarana
     */
    public function viewAny(User $user): bool
    {
        return $this->canManageSarpras($user);
    }

    /**
     * Determine if the user can view the prasarana
     */
    public function view(User $user, Prasarana $prasarana): bool
    {
        return $this->canManageSarpras($user);
    }

    /**
     * Determine if the user can create prasarana
     */
    public function create(User $user): bool
    {
        return $this->canManageSarpras($user);
    }

    /**
     * Determine if the user can update the prasarana
     */
    public function update(User $user, Prasarana $prasarana): bool
    {
        return $this->canManageSarpras($user);
    }

    /**
     * Determine if the user can delete the prasarana
     */
    public function delete(User $user, Prasarana $prasarana): bool
    {
        return $this->canManageSarpras($user);
    }
}
