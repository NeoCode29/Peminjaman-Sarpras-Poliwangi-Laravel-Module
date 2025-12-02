<?php

namespace Modules\PrasaranaManagement\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\PrasaranaManagement\Entities\Prasarana;

interface PrasaranaRepositoryInterface
{
    public function findById(int $id): ?Prasarana;

    public function create(array $data): Prasarana;

    public function update(Prasarana $prasarana, array $data): Prasarana;

    public function delete(Prasarana $prasarana): bool;

    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;
}
