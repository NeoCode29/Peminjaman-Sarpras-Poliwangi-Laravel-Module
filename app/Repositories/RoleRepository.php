<?php

namespace App\Repositories;

use App\Models\Role;
use App\Repositories\Interfaces\RoleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RoleRepository implements RoleRepositoryInterface
{
    public function findById(int $id): ?Role
    {
        return Role::with(['permissions', 'users'])->find($id);
    }

    public function findByName(string $name): ?Role
    {
        return Role::where('name', $name)->first();
    }

    public function create(array $data): Role
    {
        return Role::create($data);
    }

    public function update(Role $role, array $data): Role
    {
        $role->fill($data);
        $role->save();

        return $role->fresh(['permissions', 'users']);
    }

    public function delete(Role $role): bool
    {
        return (bool) $role->delete();
    }

    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Role::withCount(['users', 'permissions'])->with(['permissions']);

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
        }

        if (!empty($filters['guard_name'])) {
            $query->where('guard_name', $filters['guard_name']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['protected'])) {
            $protectedRoles = \App\Constants\ProtectedRoles::ROLES;
            if (filter_var($filters['protected'], FILTER_VALIDATE_BOOLEAN)) {
                // Filter only protected roles
                $query->whereIn('name', $protectedRoles);
            } else {
                // Filter only custom (non-protected) roles
                $query->whereNotIn('name', $protectedRoles);
            }
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
            $query->orderByDesc('created_at');
        }

        return $query->paginate($perPage)->appends($filters);
    }

    public function getActive(): Collection
    {
        return Role::active()->orderBy('display_name')->get();
    }

    public function countUsers(Role $role): int
    {
        return $role->users()->count();
    }
}
