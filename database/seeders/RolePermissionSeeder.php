<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RolePermissionSeeder extends Seeder
{
    private const DEFAULT_GUARD = 'web';

    private const ROLE_DEFINITIONS = [
        'Admin Sarpras' => [
            'display_name' => 'Admin (Petugas Sarpras)',
            'description' => 'Pemilik penuh operasional sarpras, master data, konfigurasi approval, dan pelaporan.',
            'permissions' => [
                'marking.cancel',
                'marking.convert_peminjaman',
                'marking.create',
                'marking.delete',
                'marking.override',
                'marking.update',
                'marking.view',
                'permission.manage',
                'permission.create',
                'permission.delete',
                'permission.edit',
                'permission.view',
                'peminjaman.view',
                'peminjaman.create',
                'peminjaman.edit',
                'peminjaman.cancel',
                'peminjaman.adjust_sarpras',
                'peminjaman.assign_global_approver',
                'peminjaman.approve_global',
                'peminjaman.reject_global',
                'peminjaman.approve_specific',
                'peminjaman.reject_specific',
                'peminjaman.validate_pickup',
                'peminjaman.validate_return',
                'report.export',
                'report.view',
                'role.manage',
                'role.view',
                'role.create',
                'role.edit',
                'role.delete',
                'sarpras.manage',
                'sarpras.view',
                'sarpras.create',
                'sarpras.edit',
                'sarpras.delete',
                'sarpras.status_update',
                'sarpras.unit_manage',
                'sarpras.assign_specific_approver',
                'system.monitoring',
                'system.settings',
                'global_approver.manage',
                'user.manage',
                'user.view',
                'user.create',
                'user.edit',
                'user.delete',
                'user.block',
                'user.unblock',
                'user.role_edit',
                'notification.view',
                'log.view',
            ],
        ],
        'Peminjam Staff' => [
            'display_name' => 'Peminjam Staff/Dosen',
            'description' => 'Staff atau dosen yang mengajukan peminjaman dengan privilese lebih tinggi dan override pengajuan mahasiswa.',
            'permissions' => [
                'sarpras.view',
                'notification.view',
                'peminjaman.view',
                'peminjaman.create',
                'peminjaman.edit',
                'peminjaman.cancel',
                'peminjaman.override',
                'marking.view',
                'marking.create',
                'marking.update',
                'marking.cancel',
                'marking.convert_peminjaman',
                'marking.override',
                'user.view',
                'user.edit',
            ],
        ],
        'Peminjam Mahasiswa' => [
            'display_name' => 'Peminjam Mahasiswa',
            'description' => 'Mahasiswa/UKM dengan fasilitas peminjaman terbatas dan tanpa override.',
            'permissions' => [
                'sarpras.view',
                'notification.view',
                'peminjaman.view',
                'peminjaman.create',
                'peminjaman.edit',
                'peminjaman.cancel',
                'marking.view',
                'marking.create',
                'marking.update',
                'marking.cancel',
                'marking.convert_peminjaman',
                'user.view',
                'user.edit',
            ],
        ],
        'Approval Global' => [
            'display_name' => 'Approval Global',
            'description' => 'Gerbang persetujuan awal lintas aset untuk memastikan kepatuhan kebijakan kampus.',
            'permissions' => [
                'sarpras.view',
                'notification.view',
                'peminjaman.approve_global',
                'peminjaman.reject_global',
                'peminjaman.view',
                'user.view',
                'user.edit',
            ],
        ],
        'Approval Spesific' => [
            'display_name' => 'Approval Spesific',
            'description' => 'Approver domain (jurusan/unit) yang memverifikasi ketersediaan dan kebutuhan teknis.',
            'permissions' => [
                'notification.view',
                'peminjaman.approve_specific',
                'peminjaman.reject_specific',
                'peminjaman.view',
                'sarpras.view',
                'user.view',
                'user.edit',
            ],
        ],
    ];

    private const PERMISSION_GROUPS = [
        'user' => [
            'manage', 'view', 'create', 'edit', 'delete', 'block', 'unblock', 'role_edit',
        ],
        'sarpras' => [
            'manage', 'view', 'create', 'edit', 'delete', 'status_update', 'unit_manage', 'assign_specific_approver',
        ],
        'peminjaman' => [
            'view', 'create', 'edit', 'cancel', 'approve_global', 'reject_global', 'approve_specific',
            'reject_specific', 'validate_pickup', 'validate_return', 'adjust_sarpras', 'assign_global_approver',
            'override',
        ],
        'marking' => [
            'create', 'view', 'update', 'cancel', 'convert_peminjaman', 'override', 'delete',
        ],
        'report' => [
            'view', 'export',
        ],
        'log' => [
            'view',
        ],
        'notification' => [
            'view',
        ],
        'system' => [
            'settings', 'monitoring',
        ],
        'global_approver' => [
            'manage',
        ],
        'permission' => [
            'manage', 'view', 'create', 'edit', 'delete',
        ],
        'role' => [
            'manage', 'view', 'create', 'edit', 'delete',
        ],
    ];

    private const PERMISSION_DESCRIPTIONS = [
        'user.manage' => 'Mengakses halaman manajemen pengguna (list, create, edit, delete).',
        'user.view' => 'Melihat daftar dan detail pengguna.',
        'user.create' => 'Menambahkan pengguna baru.',
        'user.edit' => 'Memperbarui informasi pengguna.',
        'user.delete' => 'Menghapus akun pengguna dari sistem.',
        'user.block' => 'Memblokir akses pengguna ke aplikasi.',
        'user.unblock' => 'Mengaktifkan kembali akses pengguna yang diblokir.',
        'user.role_edit' => 'Mengatur role dan permission yang dimiliki pengguna.',

        'sarpras.manage' => 'Mengakses halaman manajemen sarana dan kategori sarana (CRUD penuh).',
        'sarpras.view' => 'Melihat katalog sarana dan prasarana.',
        'sarpras.create' => 'Menambahkan sarana atau prasarana baru.',
        'sarpras.edit' => 'Memperbarui detail sarana atau prasarana.',
        'sarpras.delete' => 'Menghapus sarana atau prasarana.',
        'sarpras.status_update' => 'Mengubah status ketersediaan sarpras.',
        'sarpras.unit_manage' => 'Mengelola unit sarpras serialized.',
        'sarpras.assign_specific_approver' => 'Menetapkan approver domain untuk sarpras tertentu.',

        'peminjaman.view' => 'Melihat daftar pengajuan peminjaman.',
        'peminjaman.create' => 'Membuat pengajuan peminjaman baru.',
        'peminjaman.edit' => 'Mengubah pengajuan peminjaman yang belum final.',
        'peminjaman.cancel' => 'Membatalkan pengajuan peminjaman.',
        'peminjaman.approve_global' => 'Menyetujui pengajuan peminjaman di level global.',
        'peminjaman.reject_global' => 'Menolak pengajuan peminjaman di level global.',
        'peminjaman.approve_specific' => 'Menyetujui pengajuan peminjaman di level domain.',
        'peminjaman.reject_specific' => 'Menolak pengajuan peminjaman di level domain.',
        'peminjaman.validate_pickup' => 'Memvalidasi proses pengambilan sarpras.',
        'peminjaman.validate_return' => 'Memvalidasi proses pengembalian sarpras.',
        'peminjaman.adjust_sarpras' => 'Mengatur ulang detail sarpras pada pengajuan.',
        'peminjaman.assign_global_approver' => 'Menetapkan approver global untuk peminjaman.',
        'peminjaman.override' => 'Mengambil alih keputusan peminjaman.',

        'marking.create' => 'Membuat permintaan penandaan jadwal.',
        'marking.view' => 'Melihat daftar permintaan marking.',
        'marking.update' => 'Memperbarui detail permintaan marking.',
        'marking.cancel' => 'Membatalkan permintaan marking.',
        'marking.convert_peminjaman' => 'Mengonversi permintaan marking menjadi peminjaman.',
        'marking.override' => 'Mengambil alih keputusan terkait marking.',
        'marking.delete' => 'Menghapus permintaan marking.',

        'report.view' => 'Melihat laporan peminjaman dan penggunaan sarpras.',
        'report.export' => 'Mengekspor laporan ke format unduhan.',

        'log.view' => 'Mengakses catatan aktivitas sistem.',

        'notification.view' => 'Mengakses daftar notifikasi yang diterima.',

        'system.settings' => 'Mengelola konfigurasi sistem inti.',
        'system.monitoring' => 'Memantau status dan kesehatan sistem.',

        'global_approver.manage' => 'Mengelola global approver untuk persetujuan peminjaman.',

        'permission.manage' => 'Mengakses halaman manajemen permission (list, create, edit, delete).',
        'permission.view' => 'Melihat daftar permission yang tersedia.',
        'permission.create' => 'Membuat permission baru.',
        'permission.edit' => 'Memperbarui permission yang ada.',
        'permission.delete' => 'Menghapus permission dari sistem.',

        'role.manage' => 'Mengakses halaman manajemen role (list, create, edit, delete).',
        'role.view' => 'Melihat daftar role yang tersedia.',
        'role.create' => 'Membuat role baru.',
        'role.edit' => 'Memperbarui role yang ada.',
        'role.delete' => 'Menghapus role dari sistem.',
    ];

    public function run(): void
    {
        DB::transaction(function () {
            $roleDefinitions = $this->roleDefinitions();

            $permissions = $this->seedPermissions();
            $roles = $this->seedRoles($roleDefinitions);

            $this->syncRolePermissions($roles, $permissions, $roleDefinitions);
        });
    }

    private function roleDefinitions(): Collection
    {
        return collect(self::ROLE_DEFINITIONS);
    }

    private function seedPermissions(): Collection
    {
        $descriptions = self::PERMISSION_DESCRIPTIONS;

        return collect(self::PERMISSION_GROUPS)
            ->flatMap(function (array $actions, string $category) use ($descriptions) {
                return collect($actions)->mapWithKeys(function (string $action) use ($category, $descriptions) {
                    $permissionName = sprintf('%s.%s', $category, $action);

                    $permission = Permission::query()->updateOrCreate(
                        ['name' => $permissionName],
                        [
                            'guard_name' => self::DEFAULT_GUARD,
                            'display_name' => Str::headline($action),
                            'description' => $descriptions[$permissionName] ?? null,
                            'category' => $category,
                            'is_active' => true,
                        ]
                    );

                    return [$permissionName => $permission];
                });
            });
    }

    private function seedRoles(Collection $roleDefinitions): Collection
    {
        return $roleDefinitions->map(function (array $roleDefinition, string $roleKey) {
            $role = Role::query()->updateOrCreate(
                ['name' => $roleKey],
                [
                    'display_name' => $roleDefinition['display_name'],
                    'description' => $roleDefinition['description'],
                    'guard_name' => self::DEFAULT_GUARD,
                    'is_active' => true,
                ]
            );

            return $role;
        });
    }

    private function syncRolePermissions(Collection $roles, Collection $permissions, Collection $roleDefinitions): void
    {
        $roleDefinitions->each(function (array $roleDefinition, string $roleKey) use ($roles, $permissions) {
            $role = $roles->get($roleKey);

            if (! $role) {
                return;
            }

            $permissionIds = collect($roleDefinition['permissions'] ?? [])
                ->map(fn (string $permissionName) => $permissions->get($permissionName))
                ->filter()
                ->map->id
                ->values()
                ->all();

            $role->syncPermissions($permissionIds);
        });
    }
}
