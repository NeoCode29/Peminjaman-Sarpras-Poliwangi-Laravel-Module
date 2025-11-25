<?php

namespace App\Constants;

class ProtectedRoles
{
    /**
     * Daftar role yang tidak boleh dihapus atau dimodifikasi secara sembarangan.
     */
    public const ROLES = [
        'Admin Sarpras',
        'Peminjam Staff',
        'Peminjam Mahasiswa',
        'Approval Global',
        'Approval Spesific',
    ];

    /**
     * Check if a role name is protected.
     */
    public static function isProtected(string $roleName): bool
    {
        return in_array($roleName, self::ROLES, true);
    }
}
