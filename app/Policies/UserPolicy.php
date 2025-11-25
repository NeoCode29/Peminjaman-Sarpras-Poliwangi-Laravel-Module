<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Helper untuk cek apakah user memiliki permission manage user.
     * Permission ini diperlukan untuk akses halaman manajemen user.
     */
    protected function canManageUsers(User $user): bool
    {
        return $user->hasPermissionTo('user.manage');
    }

    public function viewAny(User $user): bool
    {
        // Hanya user dengan permission user.manage yang boleh akses halaman manajemen user
        return $this->canManageUsers($user);
    }

    public function view(User $user, User $target): bool
    {
        // User bisa lihat profil sendiri, atau user dengan permission manage bisa lihat semua
        if ($user->id === $target->id) {
            return true;
        }

        return $this->canManageUsers($user);
    }

    public function create(User $user): bool
    {
        // Hanya user dengan permission user.manage yang boleh buat user baru
        return $this->canManageUsers($user);
    }

    public function update(User $user, User $target): bool
    {
        // User bisa edit profil sendiri (di halaman profile, bukan manajemen user)
        if ($user->id === $target->id) {
            return true;
        }

        // Edit user lain hanya bisa user dengan permission manage
        return $this->canManageUsers($user);
    }

    public function delete(User $user, User $target): bool
    {
        // Tidak bisa hapus diri sendiri
        if ($user->id === $target->id) {
            return false;
        }

        // Hanya user dengan permission user.manage yang boleh hapus user
        return $this->canManageUsers($user);
    }

    public function changePassword(User $user, User $target): bool
    {
        // User selalu bisa ganti password sendiri (di halaman profil)
        if ($user->id === $target->id) {
            return true;
        }

        // Hanya user dengan permission manage yang bisa ganti password user lain
        return $this->canManageUsers($user);
    }

    public function block(User $user, User $target): bool
    {
        // Tidak bisa block diri sendiri
        if ($user->id === $target->id) {
            return false;
        }

        // Hanya user dengan permission user.manage yang boleh block user
        return $this->canManageUsers($user);
    }

    public function unblock(User $user, User $target): bool
    {
        // Tidak bisa unblock diri sendiri (edge case)
        if ($user->id === $target->id) {
            return false;
        }

        // Hanya user dengan permission user.manage yang boleh unblock user
        return $this->canManageUsers($user);
    }
}
