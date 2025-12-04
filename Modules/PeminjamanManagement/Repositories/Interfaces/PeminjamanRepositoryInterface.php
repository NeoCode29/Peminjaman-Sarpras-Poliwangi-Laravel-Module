<?php

namespace Modules\PeminjamanManagement\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\PeminjamanManagement\Entities\Peminjaman;

interface PeminjamanRepositoryInterface
{
    /**
     * Get all peminjaman with filters and pagination.
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get peminjaman by ID.
     */
    public function findById(int $id): ?Peminjaman;

    /**
     * Get peminjaman by ID with relations.
     */
    public function findByIdWithRelations(int $id, array $relations = []): ?Peminjaman;

    /**
     * Create new peminjaman.
     */
    public function create(array $data): Peminjaman;

    /**
     * Update peminjaman.
     */
    public function update(Peminjaman $peminjaman, array $data): Peminjaman;

    /**
     * Delete peminjaman.
     */
    public function delete(Peminjaman $peminjaman): bool;

    /**
     * Get peminjaman for user.
     */
    public function getForUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get active peminjaman for user.
     */
    public function getActiveForUser(int $userId): Collection;

    /**
     * Count active peminjaman for user.
     */
    public function countActiveForUser(int $userId): int;

    /**
     * Get peminjaman by status.
     */
    public function getByStatus(string $status, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get pending peminjaman for approver.
     */
    public function getPendingForApprover(int $approverId): Collection;

    /**
     * Get peminjaman in date range.
     */
    public function getInDateRange(string $startDate, string $endDate): Collection;

    /**
     * Get peminjaman in konflik group.
     */
    public function getInKonflikGroup(string $konflikCode): Collection;

    /**
     * Update status.
     */
    public function updateStatus(Peminjaman $peminjaman, string $status, array $additionalData = []): Peminjaman;
}
