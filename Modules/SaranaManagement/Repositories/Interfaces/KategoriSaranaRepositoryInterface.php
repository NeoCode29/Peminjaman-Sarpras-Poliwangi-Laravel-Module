<?php

namespace Modules\SaranaManagement\Repositories\Interfaces;

use Modules\SaranaManagement\Entities\KategoriSarana;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface KategoriSaranaRepositoryInterface
{
    /**
     * Find kategori by ID
     */
    public function findById(int $id): ?KategoriSarana;

    /**
     * Create new kategori
     */
    public function create(array $data): KategoriSarana;

    /**
     * Update existing kategori
     */
    public function update(KategoriSarana $kategori, array $data): KategoriSarana;

    /**
     * Delete kategori
     */
    public function delete(KategoriSarana $kategori): bool;

    /**
     * Get all kategori with filters and pagination
     */
    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Get all kategori without pagination (for select options)
     */
    public function getAllWithoutPagination(): Collection;
}
