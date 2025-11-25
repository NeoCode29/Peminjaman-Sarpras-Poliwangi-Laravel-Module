<?php

namespace App\Repositories\Interfaces;

use App\Models\StaffEmployee;

interface StaffEmployeeRepositoryInterface
{
    /**
     * Find staff employee by user ID
     */
    public function findByUserId(int $userId): ?StaffEmployee;

    /**
     * Create new staff employee record
     */
    public function create(array $attributes): StaffEmployee;

    /**
     * Update staff employee record
     */
    public function update(StaffEmployee $staff, array $attributes): StaffEmployee;

    /**
     * Delete staff employee record
     */
    public function delete(StaffEmployee $staff): bool;
}
