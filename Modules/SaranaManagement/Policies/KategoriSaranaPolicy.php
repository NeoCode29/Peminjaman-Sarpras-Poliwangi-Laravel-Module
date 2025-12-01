<?php

namespace Modules\SaranaManagement\Policies;

use App\Models\User;
use Modules\SaranaManagement\Entities\KategoriSarana;

class KategoriSaranaPolicy
{
    /**
     * Check if user can manage sarpras (kategori sarana)
     */
    protected function canManageSarpras(User $user): bool
    {
        return $user->hasPermissionTo('sarpras.manage');
    }

    /**
     * Determine if the user can view any kategori saranas
     */
    public function viewAny(User $user): bool
    {
        return $this->canManageSarpras($user);
    }

    /**
     * Determine if the user can view the kategori sarana
     */
    public function view(User $user, KategoriSarana $kategoriSarana): bool
    {
        return $this->canManageSarpras($user);
    }

    /**
     * Determine if the user can create kategori saranas
     */
    public function create(User $user): bool
    {
        return $this->canManageSarpras($user);
    }

    /**
     * Determine if the user can update the kategori sarana
     */
    public function update(User $user, KategoriSarana $kategoriSarana): bool
    {
        return $this->canManageSarpras($user);
    }

    /**
     * Determine if the user can delete the kategori sarana
     */
    public function delete(User $user, KategoriSarana $kategoriSarana): bool
    {
        return $this->canManageSarpras($user);
    }
}
