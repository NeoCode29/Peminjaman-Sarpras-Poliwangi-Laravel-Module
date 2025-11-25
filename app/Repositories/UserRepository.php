<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

class UserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::with(['roles', 'student', 'staffEmployee'])->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findByUsername(string $username): ?User
    {
        return User::where('username', $username)->first();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->fill($data);
        $user->save();

        return $user->fresh(['roles', 'student', 'staffEmployee']);
    }

    public function delete(User $user): bool
    {
        return (bool) $user->delete();
    }

    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = User::query()->with(['roles', 'student', 'staffEmployee']);

        $this->applyFilters($query, $filters);

        if (! empty($filters['order_by'])) {
            $query->orderBy($filters['order_by'], $filters['order_direction'] ?? 'asc');
        } else {
            $query->orderByDesc('created_at');
        }

        return $query->paginate($perPage)->appends($filters);
    }

    public function getActive(array $filters = []): Collection
    {
        $query = User::query()->active()->with(['roles']);

        $this->applyFilters($query, $filters);

        return $query->get();
    }

    public function block(User $user, ?string $blockedUntil = null, ?string $reason = null): User
    {
        $data = [
            'status' => 'blocked',
            'blocked_reason' => $reason,
        ];

        if ($blockedUntil !== null) {
            $data['blocked_until'] = Carbon::parse($blockedUntil);
        }

        return $this->update($user, $data);
    }

    public function unblock(User $user): User
    {
        return $this->update($user, [
            'status' => 'active',
            'blocked_until' => null,
            'blocked_reason' => null,
        ]);
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['user_type'])) {
            $query->where('user_type', $filters['user_type']);
        }

        if (! empty($filters['role_id'])) {
            $query->whereHas('roles', fn ($q) => $q->where('roles.id', $filters['role_id']));
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->search($search);
        }
    }
}
