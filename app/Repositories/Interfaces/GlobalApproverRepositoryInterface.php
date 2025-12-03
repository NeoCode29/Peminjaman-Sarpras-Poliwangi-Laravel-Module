<?php

namespace App\Repositories\Interfaces;

use App\Models\GlobalApprover;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface GlobalApproverRepositoryInterface
{
    /**
     * Get all global approvers with pagination
     */
    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Get all active global approvers
     */
    public function getActive(): Collection;

    /**
     * Find global approver by ID
     */
    public function findById(int $id): ?GlobalApprover;

    /**
     * Find global approver by user ID
     */
    public function findByUserId(int $userId): ?GlobalApprover;

    /**
     * Create new global approver
     */
    public function create(array $data): GlobalApprover;

    /**
     * Update global approver
     */
    public function update(GlobalApprover $globalApprover, array $data): GlobalApprover;

    /**
     * Delete global approver
     */
    public function delete(GlobalApprover $globalApprover): bool;

    /**
     * Check if user is already a global approver
     */
    public function isUserApprover(int $userId): bool;

    /**
     * Check if combination of user_id and approval_level exists
     */
    public function existsCombination(int $userId, int $level, ?int $excludeId = null): bool;

    /**
     * Get approvers by level
     */
    public function getByLevel(int $level): Collection;

    /**
     * Toggle active status
     */
    public function toggleActive(GlobalApprover $globalApprover): GlobalApprover;
}
