<?php

namespace Modules\PrasaranaManagement\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\PrasaranaManagement\Entities\Prasarana;
use Modules\PrasaranaManagement\Repositories\Interfaces\PrasaranaRepositoryInterface;

class PrasaranaRepository implements PrasaranaRepositoryInterface
{
    public function findById(int $id): ?Prasarana
    {
        return Prasarana::with(['kategori', 'images', 'createdBy'])->find($id);
    }

    public function create(array $data): Prasarana
    {
        return Prasarana::create($data);
    }

    public function update(Prasarana $prasarana, array $data): Prasarana
    {
        $prasarana->fill($data)->save();

        return $prasarana->fresh(['kategori', 'images', 'createdBy']);
    }

    public function delete(Prasarana $prasarana): bool
    {
        return (bool) $prasarana->delete();
    }

    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Prasarana::query()->with(['kategori', 'images']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('lokasi', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['kategori_id'])) {
            $query->where('kategori_id', $filters['kategori_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['lokasi'])) {
            $query->where('lokasi', 'like', "%{$filters['lokasi']}%");
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }
}
