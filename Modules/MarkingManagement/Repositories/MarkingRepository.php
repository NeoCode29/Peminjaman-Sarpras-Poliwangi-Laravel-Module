<?php

namespace Modules\MarkingManagement\Repositories;

use Modules\MarkingManagement\Entities\Marking;
use Modules\MarkingManagement\Repositories\Interfaces\MarkingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class MarkingRepository implements MarkingRepositoryInterface
{
    /**
     * Find marking by ID
     */
    public function findById(int $id): ?Marking
    {
        return Marking::with(['user', 'ukm', 'prasarana'])->find($id);
    }

    /**
     * Create new marking
     */
    public function create(array $data): Marking
    {
        return Marking::create($data);
    }

    /**
     * Update existing marking
     */
    public function update(Marking $marking, array $data): Marking
    {
        $marking->fill($data)->save();
        return $marking->fresh(['user', 'ukm', 'prasarana']);
    }

    /**
     * Delete marking
     */
    public function delete(Marking $marking): bool
    {
        return (bool) $marking->delete();
    }

    /**
     * Get all markings with filters and pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Marking::query()->with(['user', 'ukm', 'prasarana']);

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by user
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filter by date range
        if (!empty($filters['start_date'])) {
            $query->where('start_datetime', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->where('end_datetime', '<=', $filters['end_date']);
        }

        // Search
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get markings by user
     */
    public function getByUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['user_id'] = $userId;
        return $this->getAll($filters, $perPage);
    }

    /**
     * Get active markings
     */
    public function getActiveMarkings(): Collection
    {
        return Marking::active()
            ->with(['user', 'ukm', 'prasarana'])
            ->get();
    }

    /**
     * Get expired markings that need to be updated
     */
    public function getExpiredMarkings(): Collection
    {
        return Marking::where('status', Marking::STATUS_ACTIVE)
            ->where('expires_at', '<=', now())
            ->get();
    }

    /**
     * Get markings expiring soon
     */
    public function getExpiringSoon(int $hours = 24): Collection
    {
        return Marking::expiringSoon($hours)
            ->with(['user', 'prasarana'])
            ->get();
    }

    /**
     * Check for conflicts with existing markings
     */
    public function checkConflicts(array $data, ?int $excludeId = null): ?Marking
    {
        $query = Marking::where('status', Marking::STATUS_ACTIVE)
            ->where('expires_at', '>', now())
            ->where(function ($q) use ($data) {
                $q->whereBetween('start_datetime', [$data['start_datetime'], $data['end_datetime']])
                  ->orWhereBetween('end_datetime', [$data['start_datetime'], $data['end_datetime']])
                  ->orWhere(function ($q2) use ($data) {
                      $q2->where('start_datetime', '<=', $data['start_datetime'])
                         ->where('end_datetime', '>=', $data['end_datetime']);
                  });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        // Check prasarana conflicts
        if (!empty($data['prasarana_id'])) {
            return $query->where('prasarana_id', $data['prasarana_id'])->first();
        }

        // Check custom location conflicts (same location)
        if (!empty($data['lokasi_custom'])) {
            return $query->where('lokasi_custom', $data['lokasi_custom'])->first();
        }

        return null;
    }
}
