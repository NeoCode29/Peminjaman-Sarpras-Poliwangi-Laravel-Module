<?php

namespace App\Repositories;

use App\Models\GlobalApprover;
use App\Repositories\Interfaces\GlobalApproverRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GlobalApproverRepository implements GlobalApproverRepositoryInterface
{
    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = GlobalApprover::query()->with('user');

        $this->applyFilters($query, $filters);

        $query->orderBy('approval_level')
            ->orderByDesc('created_at');

        return $query->paginate($perPage)->appends($filters);
    }

    public function getActive(): Collection
    {
        return GlobalApprover::query()
            ->active()
            ->with('user')
            ->orderBy('approval_level')
            ->get();
    }

    public function findById(int $id): ?GlobalApprover
    {
        return GlobalApprover::with('user')->find($id);
    }

    public function findByUserId(int $userId): ?GlobalApprover
    {
        return GlobalApprover::where('user_id', $userId)->first();
    }

    public function create(array $data): GlobalApprover
    {
        return GlobalApprover::create($data);
    }

    public function update(GlobalApprover $globalApprover, array $data): GlobalApprover
    {
        $globalApprover->fill($data);
        $globalApprover->save();

        return $globalApprover->fresh('user');
    }

    public function delete(GlobalApprover $globalApprover): bool
    {
        return (bool) $globalApprover->delete();
    }

    public function isUserApprover(int $userId): bool
    {
        return GlobalApprover::where('user_id', $userId)->exists();
    }

    public function existsCombination(int $userId, int $level, ?int $excludeId = null): bool
    {
        $query = GlobalApprover::where('user_id', $userId)
            ->where('approval_level', $level);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function getByLevel(int $level): Collection
    {
        return GlobalApprover::query()
            ->byLevel($level)
            ->active()
            ->with('user')
            ->get();
    }

    public function toggleActive(GlobalApprover $globalApprover): GlobalApprover
    {
        $globalApprover->is_active = ! $globalApprover->is_active;
        $globalApprover->save();

        return $globalApprover->fresh('user');
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (! empty($filters['approval_level'])) {
            $query->where('approval_level', $filters['approval_level']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }
    }
}
