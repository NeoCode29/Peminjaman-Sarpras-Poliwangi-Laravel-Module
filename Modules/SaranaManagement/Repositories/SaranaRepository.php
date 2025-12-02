<?php

namespace Modules\SaranaManagement\Repositories;

use Modules\SaranaManagement\Entities\Sarana;
use Modules\SaranaManagement\Repositories\Interfaces\SaranaRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SaranaRepository implements SaranaRepositoryInterface
{
    /**
     * Find sarana by ID with relations
     */
    public function findById(int $id): ?Sarana
    {
        return Sarana::with(['kategori'])->find($id);
    }

    /**
     * Create new sarana
     */
    public function create(array $data): Sarana
    {
        return Sarana::create($data);
    }

    /**
     * Update existing sarana
     */
    public function update(Sarana $sarana, array $data): Sarana
    {
        $sarana->fill($data)->save();
        return $sarana->fresh(['kategori']);
    }

    /**
     * Delete sarana
     */
    public function delete(Sarana $sarana): bool
    {
        return (bool) $sarana->delete();
    }

    /**
     * Get all saranas with filters and pagination
     */
    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Sarana::query()->with(['kategori']);

        // Search filter
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Kategori filter
        if (!empty($filters['kategori_id'])) {
            $query->byKategori($filters['kategori_id']);
        }

        // Kondisi filter
        if (!empty($filters['kondisi'])) {
            $query->kondisi($filters['kondisi']);
        }

        // Status ketersediaan filter
        if (!empty($filters['status_ketersediaan'])) {
            $query->statusKetersediaan($filters['status_ketersediaan']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Find sarana by kode
     */
    public function findByKode(string $kode): ?Sarana
    {
        return Sarana::with(['kategori'])->where('kode_sarana', $kode)->first();
    }

    /**
     * Get saranas by kategori
     */
    public function getByKategori(int $kategoriId, int $perPage = 20): LengthAwarePaginator
    {
        return Sarana::with(['kategori'])
            ->byKategori($kategoriId)
            ->latest()
            ->paginate($perPage);
    }
}
