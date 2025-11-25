<?php

namespace App\Repositories\Interfaces;

use App\Models\Student;

interface StudentRepositoryInterface
{
    /**
     * Find student by user ID
     */
    public function findByUserId(int $userId): ?Student;

    /**
     * Create new student record
     */
    public function create(array $attributes): Student;

    /**
     * Update student record
     */
    public function update(Student $student, array $attributes): Student;

    /**
     * Delete student record
     */
    public function delete(Student $student): bool;
}
