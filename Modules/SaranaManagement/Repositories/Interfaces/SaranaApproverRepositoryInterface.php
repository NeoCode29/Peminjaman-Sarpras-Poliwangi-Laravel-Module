<?php

namespace Modules\SaranaManagement\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\SaranaManagement\Entities\SaranaApprover;

interface SaranaApproverRepositoryInterface
{
    /**
     * Find approver mapping by ID
     */
    public function findById(int $id): ?SaranaApprover;

    /**
     * Create new approver mapping
     */
    public function create(array $data): SaranaApprover;

    /**
     * Update existing approver mapping
     */
    public function update(SaranaApprover $approver, array $data): SaranaApprover;

    /**
     * Delete approver mapping
     */
    public function delete(SaranaApprover $approver): bool;

    /**
     * Get approvers for a specific sarana
     */
    public function getBySarana(int $saranaId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Get all active approvers for sarana (no pagination)
     */
    public function getActiveBySarana(int $saranaId): array;

    /**
     * Check if combination already exists
     */
    public function existsForSaranaAndUser(int $saranaId, int $userId, int $approvalLevel, ?int $ignoreId = null): bool;
}
