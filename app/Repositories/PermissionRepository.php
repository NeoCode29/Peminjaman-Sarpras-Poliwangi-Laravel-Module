<?php

namespace App\Repositories;

use App\Models\Permission;
use App\Repositories\Interfaces\PermissionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PermissionRepository implements PermissionRepositoryInterface
{
    public function findById(int $id): ?Permission
    {
        return Permission::with('roles')->find($id);
    }

    public function findByName(string $name): ?Permission
    {
        return Permission::where('name', $name)->first();
    }

    public function create(array $data): Permission
    {
        return Permission::create($data);
    }

    public function update(Permission $permission, array $data): Permission
    {
        $permission->fill($data);
        $permission->save();

        return $permission->fresh('roles');
    }

    public function delete(Permission $permission): bool
    {
        return (bool) $permission->delete();
    }

    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Permission::with('roles');

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('display_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['order_by'])) {
            $direction = $filters['order_direction'] ?? 'asc';
            $query->orderBy($filters['order_by'], $direction);
        } else {
            $query->orderBy('category')->orderBy('name');
        }

        return $query->paginate($perPage)->appends($filters);
    }

    public function getActiveByCategory(): Collection
    {
        return Permission::active()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Permission $permission) => $permission->category ?? 'uncategorized');
    }

    public function getByIds(array $ids): Collection
    {
        return Permission::whereIn('id', $ids)->get();
    }

    public function countRoles(Permission $permission): int
    {
        return $permission->roles()->count();
    }
}
