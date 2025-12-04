<?php

namespace Modules\PeminjamanManagement\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\PeminjamanManagement\Entities\Peminjaman;
use Modules\PeminjamanManagement\Entities\PeminjamanApprovalWorkflow;
use Modules\PeminjamanManagement\Repositories\Interfaces\PeminjamanRepositoryInterface;

class PeminjamanRepository implements PeminjamanRepositoryInterface
{
    /**
     * Get all peminjaman with filters and pagination.
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Peminjaman::query()
            ->with(['user', 'prasarana', 'items.sarana', 'approvalStatus']);

        $this->applyFilters($query, $filters);

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get peminjaman by ID.
     */
    public function findById(int $id): ?Peminjaman
    {
        return Peminjaman::find($id);
    }

    /**
     * Get peminjaman by ID with relations.
     */
    public function findByIdWithRelations(int $id, array $relations = []): ?Peminjaman
    {
        $defaultRelations = [
            'user',
            'prasarana',
            'ukm',
            'items.sarana.units',
            'items.units.unit',
            'approvalStatus',
            'approvalWorkflow.approver',
            'approvalWorkflow.overriddenBy',
            'approvalWorkflow.sarana',
            'approvalWorkflow.prasarana',
            'itemUnits.unit',
            'approvedBy',
            'pickupValidatedBy',
            'returnValidatedBy',
            'cancelledBy',
        ];

        $relations = !empty($relations) ? $relations : $defaultRelations;

        return Peminjaman::with($relations)->find($id);
    }

    /**
     * Create new peminjaman.
     */
    public function create(array $data): Peminjaman
    {
        return Peminjaman::create($data);
    }

    /**
     * Update peminjaman.
     */
    public function update(Peminjaman $peminjaman, array $data): Peminjaman
    {
        $peminjaman->fill($data)->save();
        return $peminjaman->fresh();
    }

    /**
     * Delete peminjaman.
     */
    public function delete(Peminjaman $peminjaman): bool
    {
        return (bool) $peminjaman->delete();
    }

    /**
     * Get peminjaman for user.
     */
    public function getForUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Peminjaman::query()
            ->with(['prasarana', 'items.sarana', 'approvalStatus'])
            ->forUser($userId);

        $this->applyFilters($query, $filters);

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get active peminjaman for user.
     */
    public function getActiveForUser(int $userId): Collection
    {
        return Peminjaman::query()
            ->with(['prasarana', 'items.sarana'])
            ->forUser($userId)
            ->active()
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Count active peminjaman for user.
     */
    public function countActiveForUser(int $userId): int
    {
        return Peminjaman::query()
            ->forUser($userId)
            ->active()
            ->count();
    }

    /**
     * Get peminjaman by status.
     */
    public function getByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return Peminjaman::query()
            ->with(['user', 'prasarana', 'items.sarana', 'approvalStatus'])
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get pending peminjaman for approver.
     */
    public function getPendingForApprover(int $approverId): Collection
    {
        $peminjamanIds = PeminjamanApprovalWorkflow::query()
            ->forApprover($approverId)
            ->pending()
            ->pluck('peminjaman_id')
            ->unique();

        return Peminjaman::query()
            ->with(['user', 'prasarana', 'items.sarana', 'approvalStatus', 'approvalWorkflow'])
            ->whereIn('id', $peminjamanIds)
            ->pending()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get peminjaman in date range.
     */
    public function getInDateRange(string $startDate, string $endDate): Collection
    {
        return Peminjaman::query()
            ->with(['user', 'prasarana', 'items.sarana'])
            ->dateRange($startDate, $endDate)
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Get peminjaman in konflik group.
     */
    public function getInKonflikGroup(string $konflikCode): Collection
    {
        return Peminjaman::query()
            ->with(['user', 'prasarana', 'items.sarana'])
            ->inKonflikGroup($konflikCode)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Update status.
     */
    public function updateStatus(Peminjaman $peminjaman, string $status, array $additionalData = []): Peminjaman
    {
        $data = array_merge(['status' => $status], $additionalData);
        $peminjaman->fill($data)->save();
        return $peminjaman->fresh();
    }

    /**
     * Apply filters to query.
     */
    protected function applyFilters($query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['user_id'])) {
            $query->forUser($filters['user_id']);
        }

        if (!empty($filters['prasarana_id'])) {
            $query->where('prasarana_id', $filters['prasarana_id']);
        }

        if (!empty($filters['start_date'])) {
            $query->where('start_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('end_date', '<=', $filters['end_date']);
        }

        if (!empty($filters['ukm_id'])) {
            $query->where('ukm_id', $filters['ukm_id']);
        }
    }
}
