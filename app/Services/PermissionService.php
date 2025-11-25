<?php

namespace App\Services;

use App\Models\Permission;
use App\Repositories\Interfaces\PermissionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use RuntimeException;

class PermissionService
{
    public function __construct(
        private readonly PermissionRepositoryInterface $permissionRepository,
        private readonly DatabaseManager $database
    ) {
    }

    public function getPermissions(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->permissionRepository->getAll($filters, $perPage);
    }

    public function getActivePermissionsGrouped()
    {
        // Kembalikan Collection yang dikelompokkan per kategori.
        // Pemanggil (misalnya RoleManagementController) bisa memilih untuk
        // menggunakannya sebagai grouped collection atau di-flatten sesuai kebutuhan.
        return $this->permissionRepository->getActiveByCategory();
    }

    public function getPermissionById(int $id): Permission
    {
        $permission = $this->permissionRepository->findById($id);

        if (! $permission) {
            throw (new ModelNotFoundException())->setModel(Permission::class, [$id]);
        }

        return $permission;
    }

    public function createPermission(array $data): Permission
    {
        $payload = $this->preparePermissionPayload($data);

        return $this->database->transaction(function () use ($payload) {
            return $this->permissionRepository->create($payload);
        });
    }

    public function updatePermission(Permission $permission, array $data): Permission
    {
        $payload = $this->preparePermissionPayload($data, $permission);

        return $this->database->transaction(function () use ($permission, $payload) {
            return $this->permissionRepository->update($permission, $payload);
        });
    }

    public function deletePermission(Permission $permission): void
    {
        if ($this->permissionRepository->countRoles($permission) > 0) {
            throw new RuntimeException('Permission tidak dapat dihapus karena masih digunakan oleh role.');
        }

        $this->database->transaction(function () use ($permission) {
            $this->permissionRepository->delete($permission);
        });
    }

    public function toggleStatus(Permission $permission): Permission
    {
        return $this->database->transaction(function () use ($permission) {
            return $this->permissionRepository->update($permission, [
                'is_active' => ! $permission->is_active,
            ]);
        });
    }

    private function preparePermissionPayload(array $data, ?Permission $existing = null): array
    {
        $payload = [
            'name' => $this->sanitizePermissionName($data['name'] ?? $existing?->name ?? ''),
            'guard_name' => $data['guard_name'] ?? $existing?->guard_name ?? 'web',
            'display_name' => $data['display_name'] ?? $existing?->display_name,
            'description' => $data['description'] ?? $existing?->description,
            'category' => $data['category'] ?? $existing?->category,
            'is_active' => $data['is_active'] ?? $existing?->is_active ?? true,
        ];

        if (empty($payload['name'])) {
            throw new RuntimeException('Nama permission wajib diisi.');
        }

        return $payload;
    }

    private function sanitizePermissionName(string $name): string
    {
        return Str::of($name)->lower()->trim()->value();
    }
}
