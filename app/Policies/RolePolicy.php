<?php

namespace App\Policies;

use App\Constants\ProtectedRoles;
use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    /**
     * Helper untuk cek apakah user memiliki permission manage role.
     * Permission ini diperlukan untuk akses halaman manajemen role.
     */
    protected function canManageRoles(User $user): bool
    {
        return $user->hasPermissionTo('role.manage');
    }

    public function viewAny(User $user): bool
    {
        // Hanya user dengan permission role.manage yang boleh akses halaman manajemen role
        return $this->canManageRoles($user);
    }

    public function view(User $user, Role $role): bool
    {
        // Hanya user dengan permission role.manage yang boleh lihat detail role
        return $this->canManageRoles($user);
    }

    public function create(User $user): bool
    {
        // Hanya user dengan permission role.manage yang boleh buat role baru
        return $this->canManageRoles($user);
    }

    public function update(User $user, Role $role): bool
    {
        // Hanya user dengan permission role.manage yang boleh edit role
        return $this->canManageRoles($user);
    }

    public function delete(User $user, Role $role): bool
    {
        // Hanya user dengan permission role.manage yang boleh hapus role
        if (! $this->canManageRoles($user)) {
            return false;
        }

        // Tidak boleh hapus role yang protected
        if (ProtectedRoles::isProtected($role->name)) {
            return false;
        }

        return true;
    }

    public function toggleStatus(User $user, Role $role): bool
    {
        // Hanya user dengan permission role.manage yang boleh toggle status role
        return $this->canManageRoles($user);
    }
}
