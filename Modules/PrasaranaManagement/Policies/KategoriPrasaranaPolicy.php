<?php

namespace Modules\PrasaranaManagement\Policies;

use App\Models\User;
use Modules\PrasaranaManagement\Entities\KategoriPrasarana;

class KategoriPrasaranaPolicy
{
    /**
     * Check if user can manage kategori prasarana (sarpras)
     */
    protected function canManageSarpras(User $user): bool
    {
        return $user->hasPermissionTo('sarpras.manage');
    }

    /**
     * Determine if the user can view any kategori prasarana
     */
    public function viewAny(User $user): bool
    {
        return $this->canManageSarpras($user);
    }

    /**
     * Determine if the user can view the kategori prasarana
     */
    public function view(User $user, KategoriPrasarana $kategoriPrasarana): bool
    {
        return $this->canManageSarpras($user);
    }

    /**
     * Determine if the user can create kategori prasarana
     */
    public function create(User $user): bool
    {
        return $this->canManageSarpras($user);
    }

    /**
     * Determine if the user can update the kategori prasarana
     */
    public function update(User $user, KategoriPrasarana $kategoriPrasarana): bool
    {
        return $this->canManageSarpras($user);
    }

    /**
     * Determine if the user can delete the kategori prasarana
     */
    public function delete(User $user, KategoriPrasarana $kategoriPrasarana): bool
    {
        return $this->canManageSarpras($user);
    }
}
