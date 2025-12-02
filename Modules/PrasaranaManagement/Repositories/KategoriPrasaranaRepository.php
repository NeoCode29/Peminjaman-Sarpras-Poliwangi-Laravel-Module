<?php

namespace Modules\PrasaranaManagement\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\PrasaranaManagement\Entities\KategoriPrasarana;
use Modules\PrasaranaManagement\Repositories\Interfaces\KategoriPrasaranaRepositoryInterface;

class KategoriPrasaranaRepository implements KategoriPrasaranaRepositoryInterface
{
    public function findById(int $id): ?KategoriPrasarana
    {
        return KategoriPrasarana::withCount('prasarana')->find($id);
    }

    public function create(array $data): KategoriPrasarana
    {
        return KategoriPrasarana::create($data);
    }

    public function update(KategoriPrasarana $kategori, array $data): KategoriPrasarana
    {
        $kategori->fill($data)->save();

        return $kategori->fresh();
    }

    public function delete(KategoriPrasarana $kategori): bool
    {
        return (bool) $kategori->delete();
    }

    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = KategoriPrasarana::query()->withCount('prasarana');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($perPage);
    }

    public function getAllWithoutPagination(): Collection
    {
        return KategoriPrasarana::orderBy('name')->get();
    }
}
