<?php

namespace App\Repositories\Interfaces;

use App\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface RoleRepositoryInterface
{
    public function findById(int $id): ?Role;

    public function findByName(string $name): ?Role;

    public function create(array $data): Role;

    public function update(Role $role, array $data): Role;

    public function delete(Role $role): bool;

    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function getActive(): Collection;

    public function countUsers(Role $role): int;
}
