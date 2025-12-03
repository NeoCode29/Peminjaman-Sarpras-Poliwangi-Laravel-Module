<?php

namespace App\Services;

use App\Models\GlobalApprover;
use App\Repositories\Interfaces\GlobalApproverRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use RuntimeException;

class GlobalApproverService
{
    public function __construct(
        private readonly GlobalApproverRepositoryInterface $globalApproverRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly DatabaseManager $database
    ) {
    }

    /**
     * Get all global approvers with pagination
     */
    public function getGlobalApprovers(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->globalApproverRepository->getAll($filters, $perPage);
    }

    /**
     * Get all active global approvers
     */
    public function getActiveApprovers(): Collection
    {
        return $this->globalApproverRepository->getActive();
    }

    /**
     * Get global approver by ID
     */
    public function getGlobalApproverById(int $id): GlobalApprover
    {
        $globalApprover = $this->globalApproverRepository->findById($id);

        if (! $globalApprover) {
            throw (new ModelNotFoundException())->setModel(GlobalApprover::class, [$id]);
        }

        return $globalApprover;
    }

    /**
     * Create new global approver
     */
    public function createGlobalApprover(array $data): GlobalApprover
    {
        $this->validateUserExists((int) $data['user_id']);
        $this->validateUniqueCombination(
            (int) $data['user_id'],
            (int) $data['approval_level']
        );

        return $this->database->transaction(function () use ($data) {
            return $this->globalApproverRepository->create([
                'user_id' => $data['user_id'],
                'approval_level' => $data['approval_level'],
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    /**
     * Update global approver
     */
    public function updateGlobalApprover(GlobalApprover $globalApprover, array $data): GlobalApprover
    {
        // Validate unique combination if level is being changed
        if (isset($data['approval_level']) && $data['approval_level'] != $globalApprover->approval_level) {
            $this->validateUniqueCombination(
                $globalApprover->user_id,
                (int) $data['approval_level'],
                $globalApprover->id
            );
        }

        return $this->database->transaction(function () use ($globalApprover, $data) {
            $updateData = [];

            if (isset($data['approval_level'])) {
                $updateData['approval_level'] = $data['approval_level'];
            }

            if (isset($data['is_active'])) {
                $updateData['is_active'] = (bool) $data['is_active'];
            }

            return $this->globalApproverRepository->update($globalApprover, $updateData);
        });
    }

    /**
     * Delete global approver
     */
    public function deleteGlobalApprover(GlobalApprover $globalApprover): void
    {
        $this->database->transaction(function () use ($globalApprover) {
            $this->globalApproverRepository->delete($globalApprover);
        });
    }

    /**
     * Toggle active status
     */
    public function toggleActive(GlobalApprover $globalApprover): GlobalApprover
    {
        return $this->database->transaction(function () use ($globalApprover) {
            return $this->globalApproverRepository->toggleActive($globalApprover);
        });
    }

    /**
     * Get approvers by level
     */
    public function getApproversByLevel(int $level): Collection
    {
        return $this->globalApproverRepository->getByLevel($level);
    }

    /**
     * Check if user is a global approver
     */
    public function isUserApprover(int $userId): bool
    {
        return $this->globalApproverRepository->isUserApprover($userId);
    }

    /**
     * Get data for settings page
     */
    public function getDataForSettingsPage(array $filters = [], int $perPage = 15): array
    {
        $globalApprovers = $this->getGlobalApprovers($filters, $perPage);

        // Get users that can be approvers (users with approver-eligible roles)
        $availableUsers = $this->getEligibleApproverUsers();

        // Get available levels (1-10)
        $availableLevels = collect(range(1, 10))->map(function ($level) {
            return [
                'value' => $level,
                'label' => match ($level) {
                    1 => 'Level 1 (Primary)',
                    2 => 'Level 2 (Secondary)',
                    3 => 'Level 3 (Tertiary)',
                    default => "Level {$level}",
                },
            ];
        });

        return [
            'globalApprovers' => $globalApprovers,
            'availableUsers' => $availableUsers,
            'availableLevels' => $availableLevels,
        ];
    }

    /**
     * Validate user exists
     */
    private function validateUserExists(int $userId): void
    {
        $user = $this->userRepository->findById($userId);

        if (! $user) {
            throw new RuntimeException('User tidak ditemukan.');
        }

        if ($user->status !== 'active') {
            throw new RuntimeException('User tidak aktif.');
        }
    }

    /**
     * Validate unique combination of user_id and approval_level
     */
    private function validateUniqueCombination(int $userId, int $level, ?int $excludeId = null): void
    {
        if ($this->globalApproverRepository->existsCombination($userId, $level, $excludeId)) {
            throw new RuntimeException('User sudah menjadi global approver dengan level yang sama.');
        }
    }

    /**
     * Get users eligible to become global approvers
     * Only users with specific roles can be assigned as global approvers
     */
    private function getEligibleApproverUsers(): Collection
    {
        // Roles that are eligible to become global approvers
        $eligibleRoles = [
            'Admin Sarpras',
            'Approval Global',
            'Approval Spesific',
        ];

        return \App\Models\User::query()
            ->where('status', 'active')
            ->whereHas('roles', function ($query) use ($eligibleRoles) {
                $query->whereIn('name', $eligibleRoles);
            })
            ->orderBy('name')
            ->get();
    }
}
