<?php

namespace Modules\SaranaManagement\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\SaranaManagement\Entities\SaranaApprover;
use Modules\SaranaManagement\Repositories\Interfaces\SaranaApproverRepositoryInterface;

class SaranaApproverRepository implements SaranaApproverRepositoryInterface
{
    public function findById(int $id): ?SaranaApprover
    {
        return SaranaApprover::with(['sarana', 'approver'])->find($id);
    }

    public function create(array $data): SaranaApprover
    {
        return SaranaApprover::create($data);
    }

    public function update(SaranaApprover $approver, array $data): SaranaApprover
    {
        $approver->fill($data)->save();
        return $approver->fresh(['sarana', 'approver']);
    }

    public function delete(SaranaApprover $approver): bool
    {
        return (bool) $approver->delete();
    }

    public function getBySarana(int $saranaId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = SaranaApprover::query()
            ->with(['approver'])
            ->forSarana($saranaId)
            ->orderBy('approval_level');

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->paginate($perPage);
    }

    public function getActiveBySarana(int $saranaId): array
    {
        return SaranaApprover::query()
            ->active()
            ->forSarana($saranaId)
            ->orderBy('approval_level')
            ->get()
            ->all();
    }

    public function existsForSaranaAndUser(int $saranaId, int $userId, int $approvalLevel, ?int $ignoreId = null): bool
    {
        $query = SaranaApprover::query()
            ->forSarana($saranaId)
            ->where('approver_id', $userId)
            ->where('approval_level', $approvalLevel);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
