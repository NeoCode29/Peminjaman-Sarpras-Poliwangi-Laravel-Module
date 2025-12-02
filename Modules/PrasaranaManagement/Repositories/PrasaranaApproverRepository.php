<?php

namespace Modules\PrasaranaManagement\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\PrasaranaManagement\Entities\PrasaranaApprover;
use Modules\PrasaranaManagement\Repositories\Interfaces\PrasaranaApproverRepositoryInterface;

class PrasaranaApproverRepository implements PrasaranaApproverRepositoryInterface
{
    public function findById(int $id): ?PrasaranaApprover
    {
        return PrasaranaApprover::with(['prasarana', 'approver'])->find($id);
    }

    public function create(array $data): PrasaranaApprover
    {
        return PrasaranaApprover::create($data);
    }

    public function update(PrasaranaApprover $approver, array $data): PrasaranaApprover
    {
        $approver->fill($data)->save();

        return $approver->fresh(['prasarana', 'approver']);
    }

    public function delete(PrasaranaApprover $approver): bool
    {
        return (bool) $approver->delete();
    }

    public function getByPrasarana(int $prasaranaId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = PrasaranaApprover::query()
            ->with(['approver'])
            ->forPrasarana($prasaranaId)
            ->orderBy('approval_level');

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->paginate($perPage);
    }

    public function getActiveByPrasarana(int $prasaranaId): array
    {
        return PrasaranaApprover::query()
            ->active()
            ->forPrasarana($prasaranaId)
            ->orderBy('approval_level')
            ->get()
            ->all();
    }

    public function existsForPrasaranaAndUser(int $prasaranaId, int $userId, int $approvalLevel, ?int $ignoreId = null): bool
    {
        $query = PrasaranaApprover::query()
            ->forPrasarana($prasaranaId)
            ->where('approver_id', $userId)
            ->where('approval_level', $approvalLevel);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
