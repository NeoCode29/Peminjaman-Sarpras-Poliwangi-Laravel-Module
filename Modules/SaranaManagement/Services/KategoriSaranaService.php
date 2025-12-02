<?php

namespace Modules\SaranaManagement\Services;

use Modules\SaranaManagement\Entities\KategoriSarana;
use Modules\SaranaManagement\Repositories\Interfaces\KategoriSaranaRepositoryInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class KategoriSaranaService
{
    public function __construct(
        private readonly KategoriSaranaRepositoryInterface $kategoriRepository,
        private readonly DatabaseManager $database
    ) {}

    /**
     * Get all kategori with filters and pagination
     */
    public function getKategori(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->kategoriRepository->getAll($filters, $perPage);
    }

    /**
     * Get all kategori without pagination
     */
    public function getAllKategori(): Collection
    {
        return $this->kategoriRepository->getAllWithoutPagination();
    }

    /**
     * Find kategori by ID
     */
    public function getKategoriById(int $id): ?KategoriSarana
    {
        return $this->kategoriRepository->findById($id);
    }

    /**
     * Create new kategori
     */
    public function createKategori(array $data): KategoriSarana
    {
        return $this->database->transaction(function () use ($data) {
            return $this->kategoriRepository->create($data);
        });
    }

    /**
     * Update existing kategori
     */
    public function updateKategori(KategoriSarana $kategori, array $data): KategoriSarana
    {
        return $this->database->transaction(function () use ($kategori, $data) {
            return $this->kategoriRepository->update($kategori, $data);
        });
    }

    /**
     * Delete kategori
     */
    public function deleteKategori(KategoriSarana $kategori): void
    {
        // Check if kategori has saranas
        if ($kategori->saranas()->count() > 0) {
            throw new \RuntimeException('Kategori masih memiliki sarana, tidak dapat dihapus.');
        }

        $this->database->transaction(function () use ($kategori) {
            $this->kategoriRepository->delete($kategori);
        });
    }
}
