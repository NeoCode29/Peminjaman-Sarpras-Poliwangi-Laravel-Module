<?php

namespace Modules\PrasaranaManagement\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\PrasaranaManagement\Entities\KategoriPrasarana;

interface KategoriPrasaranaRepositoryInterface
{
    public function findById(int $id): ?KategoriPrasarana;

    public function create(array $data): KategoriPrasarana;

    public function update(KategoriPrasarana $kategori, array $data): KategoriPrasarana;

    public function delete(KategoriPrasarana $kategori): bool;

    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function getAllWithoutPagination(): Collection;
}
