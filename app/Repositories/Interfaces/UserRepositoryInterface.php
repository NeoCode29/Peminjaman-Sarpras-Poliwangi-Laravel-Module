<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function findByUsername(string $username): ?User;

    public function create(array $data): User;

    public function update(User $user, array $data): User;

    public function delete(User $user): bool;

    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function getActive(array $filters = []): Collection;

    /**
     * Tandai user sebagai diblokir dengan informasi tambahan opsional.
     */
    public function block(User $user, ?string $blockedUntil = null, ?string $reason = null): User;

    /**
     * Hapus status blokir user dan aktifkan kembali akun.
     */
    public function unblock(User $user): User;
}
