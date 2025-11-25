<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;

class PermissionPolicy
{
    /**
     * Helper untuk cek apakah user memiliki permission manage permission.
     * Permission ini diperlukan untuk akses halaman manajemen permission.
     */
    protected function canManagePermissions(User $user): bool
    {
        return $user->hasPermissionTo('permission.manage');
    }

    public function viewAny(User $user): bool
    {
        // Hanya user dengan permission permission.manage yang boleh akses halaman manajemen permission
        return $this->canManagePermissions($user);
    }

    public function view(User $user, Permission $permission): bool
    {
        // Hanya user dengan permission permission.manage yang boleh lihat detail permission
        return $this->canManagePermissions($user);
    }

    public function create(User $user): bool
    {
        // Hanya user dengan permission permission.manage yang boleh buat permission baru
        return $this->canManagePermissions($user);
    }

    public function update(User $user, Permission $permission): bool
    {
        // Hanya user dengan permission permission.manage yang boleh edit permission
        return $this->canManagePermissions($user);
    }

    public function delete(User $user, Permission $permission): bool
    {
        // Hanya user dengan permission permission.manage yang boleh hapus permission
        return $this->canManagePermissions($user);
    }

    public function toggleStatus(User $user, Permission $permission): bool
    {
        // Hanya user dengan permission permission.manage yang boleh toggle status permission
        return $this->canManagePermissions($user);
    }
}
