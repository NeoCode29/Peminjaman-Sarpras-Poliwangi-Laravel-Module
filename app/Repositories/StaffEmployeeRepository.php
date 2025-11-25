<?php

namespace App\Repositories;

use App\Models\StaffEmployee;
use App\Repositories\Interfaces\StaffEmployeeRepositoryInterface;

class StaffEmployeeRepository implements StaffEmployeeRepositoryInterface
{
    public function findByUserId(int $userId): ?StaffEmployee
    {
        return StaffEmployee::where('user_id', $userId)->first();
    }

    public function create(array $attributes): StaffEmployee
    {
        return StaffEmployee::create($attributes);
    }

    public function update(StaffEmployee $staff, array $attributes): StaffEmployee
    {
        $staff->fill($attributes);
        $staff->save();

        return $staff->fresh();
    }

    public function delete(StaffEmployee $staff): bool
    {
        return $staff->delete();
    }
}
