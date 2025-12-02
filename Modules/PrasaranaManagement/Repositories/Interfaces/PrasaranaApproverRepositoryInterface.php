<?php

namespace Modules\PrasaranaManagement\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\PrasaranaManagement\Entities\PrasaranaApprover;

interface PrasaranaApproverRepositoryInterface
{
    public function findById(int $id): ?PrasaranaApprover;

    public function create(array $data): PrasaranaApprover;

    public function update(PrasaranaApprover $approver, array $data): PrasaranaApprover;

    public function delete(PrasaranaApprover $approver): bool;

    public function getByPrasarana(int $prasaranaId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function getActiveByPrasarana(int $prasaranaId): array;

    public function existsForPrasaranaAndUser(int $prasaranaId, int $userId, int $approvalLevel, ?int $ignoreId = null): bool;
}
