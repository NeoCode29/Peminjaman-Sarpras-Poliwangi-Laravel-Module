<?php

namespace Modules\PrasaranaManagement\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Collection;
use Modules\PrasaranaManagement\Entities\KategoriPrasarana;
use Modules\PrasaranaManagement\Repositories\Interfaces\KategoriPrasaranaRepositoryInterface;

class KategoriPrasaranaService
{
    public function __construct(
        private readonly KategoriPrasaranaRepositoryInterface $kategoriRepository,
        private readonly DatabaseManager $database,
    ) {}

    public function getKategori(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->kategoriRepository->getAll($filters, $perPage);
    }

    public function getAllKategori(): Collection
    {
        return $this->kategoriRepository->getAllWithoutPagination();
    }

    public function getKategoriById(int $id): ?KategoriPrasarana
    {
        return $this->kategoriRepository->findById($id);
    }

    public function createKategori(array $data): KategoriPrasarana
    {
        return $this->database->transaction(function () use ($data) {
            return $this->kategoriRepository->create($data);
        });
    }

    public function updateKategori(KategoriPrasarana $kategori, array $data): KategoriPrasarana
    {
        return $this->database->transaction(function () use ($kategori, $data) {
            return $this->kategoriRepository->update($kategori, $data);
        });
    }

    public function deleteKategori(KategoriPrasarana $kategori): void
    {
        if ($kategori->prasarana()->count() > 0) {
            throw new \RuntimeException('Kategori masih memiliki prasarana, tidak dapat dihapus.');
        }

        $this->database->transaction(function () use ($kategori) {
            $this->kategoriRepository->delete($kategori);
        });
    }
}
