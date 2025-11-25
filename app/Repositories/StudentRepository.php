<?php

namespace App\Repositories;

use App\Models\Student;
use App\Repositories\Interfaces\StudentRepositoryInterface;

class StudentRepository implements StudentRepositoryInterface
{
    public function findByUserId(int $userId): ?Student
    {
        return Student::where('user_id', $userId)->first();
    }

    public function create(array $attributes): Student
    {
        return Student::create($attributes);
    }

    public function update(Student $student, array $attributes): Student
    {
        $student->fill($attributes);
        $student->save();

        return $student->fresh();
    }

    public function delete(Student $student): bool
    {
        return $student->delete();
    }
}
