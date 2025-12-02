<?php

namespace Modules\MarkingManagement\Policies;

use App\Models\User;
use Modules\MarkingManagement\Entities\Marking;
use Illuminate\Auth\Access\HandlesAuthorization;

class MarkingPolicy
{
    use HandlesAuthorization;

    /**
     * Check if user can manage markings (admin level)
     */
    protected function canManageMarkings(User $user): bool
    {
        return $user->hasPermissionTo('marking.manage');
    }

    /**
     * Check if user can create markings
     */
    protected function canCreateMarkings(User $user): bool
    {
        return $user->hasPermissionTo('marking.create');
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // User with manage permission can view all
        // User with create permission can view their own (handled in controller)
        return $this->canManageMarkings($user) || $this->canCreateMarkings($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Marking $marking): bool
    {
        // User can view their own marking
        if ($marking->user_id === $user->id) {
            return true;
        }

        // Admin or user with manage permission can view any marking
        return $this->canManageMarkings($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->canCreateMarkings($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Marking $marking): bool
    {
        // User can update their own marking if it's active
        if ($marking->user_id === $user->id && $marking->isActive()) {
            return true;
        }

        // User with marking_override permission can update any marking
        return $user->hasPermissionTo('marking.override');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Marking $marking): bool
    {
        // User can delete/cancel their own marking if it's active
        if ($marking->user_id === $user->id && $marking->isActive()) {
            return true;
        }

        // User with marking_override permission can delete any marking
        return $user->hasPermissionTo('marking.override');
    }

    /**
     * Determine whether the user can extend the marking.
     */
    public function extend(User $user, Marking $marking): bool
    {
        // User can extend their own marking if it's active
        if ($marking->user_id === $user->id && $marking->isActive()) {
            return true;
        }

        // User with marking_override permission can extend any marking
        return $user->hasPermissionTo('marking.override');
    }

    /**
     * Determine whether the user can convert the marking to peminjaman.
     */
    public function convert(User $user, Marking $marking): bool
    {
        // User can convert their own marking if it's active and not expired
        if ($marking->user_id === $user->id && $marking->canBeConverted()) {
            return true;
        }

        // User with marking_override permission can convert any marking
        return $user->hasPermissionTo('marking.override');
    }
}
