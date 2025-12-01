<?php

namespace Modules\SaranaManagement\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\DatabaseManager;
use Modules\SaranaManagement\Entities\SaranaApprover;
use Modules\SaranaManagement\Repositories\Interfaces\SaranaApproverRepositoryInterface;

class SaranaApproverService
{
    public function __construct(
        private readonly SaranaApproverRepositoryInterface $repository,
        private readonly DatabaseManager $database,
    ) {}

    /**
     * Get approvers for a sarana with pagination
     */
    public function getApproversForSarana(int $saranaId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->getBySarana($saranaId, $filters, $perPage);
    }

    /**
     * Get all active approvers (no pagination)
     */
    public function getActiveApproversForSarana(int $saranaId): array
    {
        return $this->repository->getActiveBySarana($saranaId);
    }

    /**
     * Create new approver mapping with duplicate check
     */
    public function createApprover(array $data): SaranaApprover
    {
        return $this->database->transaction(function () use ($data) {
            $this->ensureNotDuplicate($data['sarana_id'], $data['approver_id'], $data['approval_level']);

            return $this->repository->create($data);
        });
    }

    /**
     * Update approver mapping with duplicate check
     */
    public function updateApprover(SaranaApprover $approver, array $data): SaranaApprover
    {
        return $this->database->transaction(function () use ($approver, $data) {
            $payload = array_merge([
                'sarana_id' => $approver->sarana_id,
                'approver_id' => $approver->approver_id,
                'approval_level' => $approver->approval_level,
                'is_active' => $approver->is_active,
            ], $data);

            $this->ensureNotDuplicate(
                $payload['sarana_id'],
                $payload['approver_id'],
                $payload['approval_level'],
                $approver->id,
            );

            return $this->repository->update($approver, $data);
        });
    }

    /**
     * Delete approver mapping
     */
    public function deleteApprover(SaranaApprover $approver): void
    {
        $this->database->transaction(function () use ($approver) {
            $this->repository->delete($approver);
        });
    }

    private function ensureNotDuplicate(int $saranaId, int $userId, int $approvalLevel, ?int $ignoreId = null): void
    {
        if ($this->repository->existsForSaranaAndUser($saranaId, $userId, $approvalLevel, $ignoreId)) {
            throw new \RuntimeException('Approver dengan level yang sama sudah terdaftar untuk sarana ini.');
        }
    }
}
