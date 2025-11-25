<?php

namespace App\Services;

use App\Constants\ProtectedRoles;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Repositories\Interfaces\RoleRepositoryInterface;
use App\Repositories\Interfaces\PermissionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class RoleService
{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly PermissionRepositoryInterface $permissionRepository,
        private readonly DatabaseManager $database
    ) {
    }

    public function createRole(array $data): Role
    {
        $payload = $this->prepareRolePayload($data);

        return $this->database->transaction(function () use ($payload, $data) {
            $role = $this->roleRepository->create($payload);

            $permissionIds = $data['permissions'] ?? [];
            if (! empty($permissionIds)) {
                $this->syncPermissions($role, $permissionIds);
            }

            return $role->fresh(['permissions', 'users']);
        });
    }

    public function updateRole(Role $role, array $data): Role
    {
        // Untuk protected roles, jangan izinkan perubahan nama
        if ($this->isProtectedRole($role) && isset($data['name']) && $data['name'] !== $role->name) {
            throw new RuntimeException('Nama role sistem tidak dapat diubah.');
        }

        $payload = $this->prepareRolePayload($data, $role);

        return $this->database->transaction(function () use ($role, $payload, $data) {
            $updatedRole = $this->roleRepository->update($role, $payload);

            $permissionIds = $data['permissions'] ?? null;
            if ($permissionIds !== null) {
                $this->syncPermissions($updatedRole, $permissionIds);
            }

            return $updatedRole;
        });
    }

    public function deleteRole(Role $role): void
    {
        $this->guardDeletableRole($role);

        $this->database->transaction(function () use ($role) {
            $role->syncPermissions([]);
            $this->roleRepository->delete($role);
        });
    }

    public function getRoles(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->roleRepository->getAll($filters, $perPage);
    }

    public function getActiveRoles()
    {
        // Kembalikan koleksi Role agar bisa diakses sebagai objek (->id, ->name)
        return $this->roleRepository->getActive();
    }

    public function getRoleById(int $id): Role
    {
        $role = $this->roleRepository->findById($id);

        if (! $role) {
            throw (new ModelNotFoundException())->setModel(Role::class, [$id]);
        }

        return $role;
    }

    public function toggleStatus(Role $role): Role
    {
        $targetStatus = ! $role->is_active;

        if (! $targetStatus && $this->roleRepository->countUsers($role) > 0) {
            throw new RuntimeException('Role tidak dapat dinonaktifkan karena masih digunakan oleh user.');
        }

        return $this->database->transaction(function () use ($role, $targetStatus) {
            return $this->roleRepository->update($role, ['is_active' => $targetStatus]);
        });
    }

    public function assignRoleToUser(User $user, int $roleId): void
    {
        $role = $this->roleRepository->findById($roleId);

        if (! $role || ! $role->is_active) {
            throw new RuntimeException('Role tidak valid atau tidak aktif.');
        }

        $this->database->transaction(function () use ($user, $role) {
            $user->role_id = $role->id;
            $user->save();

            $user->syncRoles([$role]);
        });
    }

    public function syncPermissions(Role $role, array $permissionIds): void
    {
        if (empty($permissionIds)) {
            $role->syncPermissions([]);
            return;
        }

        $permissions = $this->permissionRepository->getByIds($permissionIds);
        $activePermissions = $permissions->filter(fn ($permission) => $permission->is_active);

        if ($activePermissions->count() !== count($permissionIds)) {
            throw new RuntimeException('Beberapa permission tidak valid atau tidak aktif.');
        }

        // Berikan collection Permission model ke Spatie agar tidak terjadi
        // error "There is no permission named `id`" ketika ID dikirim sebagai string.
        $role->syncPermissions($activePermissions);
    }

    private function prepareRolePayload(array $data, ?Role $existing = null): array
    {
        $payload = [
            'name' => $this->sanitizeRoleName($data['name'] ?? $existing?->name ?? ''),
            'guard_name' => $data['guard_name'] ?? $existing?->guard_name ?? 'web',
            'display_name' => $data['display_name'] ?? $existing?->display_name,
            'description' => $data['description'] ?? $existing?->description,
            'category' => $data['category'] ?? $existing?->category,
            'is_active' => Arr::get($data, 'is_active', $existing?->is_active ?? true),
        ];

        if (empty($payload['name'])) {
            throw new RuntimeException('Nama role wajib diisi.');
        }

        $role = $this->roleRepository->findByName($payload['name']);

        if ($role && ($existing === null || $role->id !== $existing->id)) {
            throw new RuntimeException('Nama role sudah digunakan.');
        }

        return $payload;
    }

    private function sanitizeRoleName(string $name): string
    {
        // Role names sekarang menggunakan PascalCase dengan spasi
        // Contoh: "Admin Sarpras", "Peminjam Staff"
        // Jangan convert ke snake_case atau lowercase
        return trim($name);
    }

    private function guardDeletableRole(Role $role): void
    {
        if ($this->isProtectedRole($role)) {
            throw new RuntimeException('Role sistem tidak dapat dihapus.');
        }

        if ($this->roleRepository->countUsers($role) > 0) {
            throw new RuntimeException('Role masih digunakan oleh user.');
        }
    }

    private function isProtectedRole(Role $role): bool
    {
        return ProtectedRoles::isProtected($role->name);
    }
}
