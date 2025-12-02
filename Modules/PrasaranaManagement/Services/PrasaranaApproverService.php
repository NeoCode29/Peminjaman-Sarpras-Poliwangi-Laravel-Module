<?php

namespace Modules\PrasaranaManagement\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\DatabaseManager;
use Modules\PrasaranaManagement\Entities\PrasaranaApprover;
use Modules\PrasaranaManagement\Repositories\Interfaces\PrasaranaApproverRepositoryInterface;

class PrasaranaApproverService
{
    public function __construct(
        private readonly PrasaranaApproverRepositoryInterface $repository,
        private readonly DatabaseManager $database,
    ) {}

    /**
     * Get approvers for a prasarana with pagination
     */
    public function getApproversForPrasarana(int $prasaranaId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->getByPrasarana($prasaranaId, $filters, $perPage);
    }

    /**
     * Get all active approvers (no pagination)
     */
    public function getActiveApproversForPrasarana(int $prasaranaId): array
    {
        return $this->repository->getActiveByPrasarana($prasaranaId);
    }

    /**
     * Create new approver mapping with duplicate check
     */
    public function createApprover(array $data): PrasaranaApprover
    {
        return $this->database->transaction(function () use ($data) {
            $this->ensureNotDuplicate($data['prasarana_id'], $data['approver_id'], $data['approval_level']);

            $approver = $this->repository->create($data);

            AuditLog::create([
                'model_type' => PrasaranaApprover::class,
                'model_id' => $approver->getKey(),
                'action' => 'created',
                'old_values' => null,
                'new_values' => $approver->getAttributes(),
                'performed_by' => Auth::id(),
                'performed_by_type' => Auth::check() ? get_class(Auth::user()) : null,
                'context' => 'prasarana_approver_service',
                'metadata' => [
                    'prasarana_id' => $approver->prasarana_id,
                    'approver_id' => $approver->approver_id,
                ],
                'performed_at' => now(),
            ]);

            return $approver;
        });
    }

    /**
     * Update approver mapping with duplicate check
     */
    public function updateApprover(PrasaranaApprover $approver, array $data): PrasaranaApprover
    {
        return $this->database->transaction(function () use ($approver, $data) {
            $original = $approver->getOriginal();

            $payload = array_merge([
                'prasarana_id' => $approver->prasarana_id,
                'approver_id' => $approver->approver_id,
                'approval_level' => $approver->approval_level,
                'is_active' => $approver->is_active,
            ], $data);

            $this->ensureNotDuplicate(
                $payload['prasarana_id'],
                $payload['approver_id'],
                $payload['approval_level'],
                $approver->id,
            );

            $updated = $this->repository->update($approver, $data);

            AuditLog::create([
                'model_type' => PrasaranaApprover::class,
                'model_id' => $updated->getKey(),
                'action' => 'updated',
                'old_values' => $original,
                'new_values' => $updated->getAttributes(),
                'performed_by' => Auth::id(),
                'performed_by_type' => Auth::check() ? get_class(Auth::user()) : null,
                'context' => 'prasarana_approver_service',
                'metadata' => [
                    'prasarana_id' => $updated->prasarana_id,
                    'approver_id' => $updated->approver_id,
                ],
                'performed_at' => now(),
            ]);

            return $updated;
        });
    }

    /**
     * Delete approver mapping
     */
    public function deleteApprover(PrasaranaApprover $approver): void
    {
        $this->database->transaction(function () use ($approver) {
            $original = $approver->getAttributes();

            $this->repository->delete($approver);

            AuditLog::create([
                'model_type' => PrasaranaApprover::class,
                'model_id' => $original['id'] ?? null,
                'action' => 'deleted',
                'old_values' => $original,
                'new_values' => null,
                'performed_by' => Auth::id(),
                'performed_by_type' => Auth::check() ? get_class(Auth::user()) : null,
                'context' => 'prasarana_approver_service',
                'metadata' => [
                    'prasarana_id' => $original['prasarana_id'] ?? null,
                    'approver_id' => $original['approver_id'] ?? null,
                ],
                'performed_at' => now(),
            ]);
        });
    }

    private function ensureNotDuplicate(int $prasaranaId, int $userId, int $approvalLevel, ?int $ignoreId = null): void
    {
        if ($this->repository->existsForPrasaranaAndUser($prasaranaId, $userId, $approvalLevel, $ignoreId)) {
            throw new \RuntimeException('Approver dengan level yang sama sudah terdaftar untuk prasarana ini.');
        }
    }
}
