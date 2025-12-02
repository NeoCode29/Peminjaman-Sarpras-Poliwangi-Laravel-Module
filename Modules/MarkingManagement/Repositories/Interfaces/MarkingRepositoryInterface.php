<?php

namespace Modules\MarkingManagement\Repositories\Interfaces;

use Modules\MarkingManagement\Entities\Marking;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface MarkingRepositoryInterface
{
    /**
     * Find marking by ID
     */
    public function findById(int $id): ?Marking;

    /**
     * Create new marking
     */
    public function create(array $data): Marking;

    /**
     * Update existing marking
     */
    public function update(Marking $marking, array $data): Marking;

    /**
     * Delete marking
     */
    public function delete(Marking $marking): bool;

    /**
     * Get all markings with filters and pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get markings by user
     */
    public function getByUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get active markings
     */
    public function getActiveMarkings(): Collection;

    /**
     * Get expired markings that need to be updated
     */
    public function getExpiredMarkings(): Collection;

    /**
     * Get markings expiring soon
     */
    public function getExpiringSoon(int $hours = 24): Collection;

    /**
     * Check for conflicts with existing markings
     */
    public function checkConflicts(array $data, ?int $excludeId = null): ?Marking;
}
