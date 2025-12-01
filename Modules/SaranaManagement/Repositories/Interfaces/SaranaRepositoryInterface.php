<?php

namespace Modules\SaranaManagement\Repositories\Interfaces;

use Modules\SaranaManagement\Entities\Sarana;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SaranaRepositoryInterface
{
    /**
     * Find sarana by ID
     */
    public function findById(int $id): ?Sarana;

    /**
     * Create new sarana
     */
    public function create(array $data): Sarana;

    /**
     * Update existing sarana
     */
    public function update(Sarana $sarana, array $data): Sarana;

    /**
     * Delete sarana
     */
    public function delete(Sarana $sarana): bool;

    /**
     * Get all saranas with filters and pagination
     */
    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Find sarana by kode
     */
    public function findByKode(string $kode): ?Sarana;

    /**
     * Get saranas by kategori
     */
    public function getByKategori(int $kategoriId, int $perPage = 20): LengthAwarePaginator;
}
