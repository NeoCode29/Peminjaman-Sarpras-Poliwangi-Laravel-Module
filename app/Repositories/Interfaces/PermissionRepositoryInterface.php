<?php

namespace App\Repositories\Interfaces;

use App\Models\Permission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface PermissionRepositoryInterface
{
    public function findById(int $id): ?Permission;

    public function findByName(string $name): ?Permission;

    public function create(array $data): Permission;

    public function update(Permission $permission, array $data): Permission;

    public function delete(Permission $permission): bool;

    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function getActiveByCategory(): Collection;

    public function getByIds(array $ids): Collection;

    public function countRoles(Permission $permission): int;
}
