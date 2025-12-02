<?php

namespace Modules\SaranaManagement\Repositories;

use Modules\SaranaManagement\Entities\KategoriSarana;
use Modules\SaranaManagement\Repositories\Interfaces\KategoriSaranaRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class KategoriSaranaRepository implements KategoriSaranaRepositoryInterface
{
    /**
     * Find kategori by ID
     */
    public function findById(int $id): ?KategoriSarana
    {
        return KategoriSarana::withCount('saranas')->find($id);
    }

    /**
     * Create new kategori
     */
    public function create(array $data): KategoriSarana
    {
        return KategoriSarana::create($data);
    }

    /**
     * Update existing kategori
     */
    public function update(KategoriSarana $kategori, array $data): KategoriSarana
    {
        $kategori->fill($data)->save();
        return $kategori->fresh();
    }

    /**
     * Delete kategori
     */
    public function delete(KategoriSarana $kategori): bool
    {
        return (bool) $kategori->delete();
    }

    /**
     * Get all kategori with filters and pagination
     */
    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = KategoriSarana::query()->withCount('saranas');

        // Search filter
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get all kategori without pagination (for select options)
     */
    public function getAllWithoutPagination(): Collection
    {
        return KategoriSarana::orderBy('nama')->get();
    }
}
