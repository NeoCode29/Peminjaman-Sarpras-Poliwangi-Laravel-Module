<?php

namespace App\Repositories\Interfaces;

use App\Models\User;

interface AuthRepositoryInterface
{
    public function findByUsername(string $username): ?User;

    public function findByEmail(string $email): ?User;

    public function findBySsoId(string $ssoId): ?User;

    public function create(array $attributes): User;

    public function update(User $user, array $attributes): User;

    public function updateLoginMetadata(User $user, array $metadata = []): User;

    public function resetFailedLoginAttempts(User $user): void;

    public function recordFailedLoginAttempt(User $user, int $maxAttempts, int $lockSeconds): void;
}
