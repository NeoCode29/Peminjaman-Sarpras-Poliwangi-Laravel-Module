<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Repositories\Interfaces\RoleRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly DatabaseManager $database
    ) {
    }

    public function getUsers(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->userRepository->getAll($filters, $perPage);
    }

    public function getUserById(int $id): User
    {
        $user = $this->userRepository->findById($id);

        if (! $user) {
            throw (new ModelNotFoundException())->setModel(User::class, [$id]);
        }

        return $user;
    }

    public function createUser(array $data): User
    {
        $role = $this->getValidRole((int) Arr::get($data, 'role_id'));

        $payload = $this->preparePayload($data, isNew: true, role: $role);

        return $this->database->transaction(function () use ($payload, $role) {
            $user = $this->userRepository->create($payload);

            $this->assignRole($user, $role);

            return $user->fresh(['roles', 'student', 'staffEmployee']);
        });
    }

    public function updateUser(User $user, array $data): User
    {
        $role = null;
        if (Arr::has($data, 'role_id')) {
            $role = $this->getValidRole((int) $data['role_id']);
        }

        $payload = $this->preparePayload($data, existing: $user, role: $role);

        return $this->database->transaction(function () use ($user, $payload, $role) {
            $updatedUser = $this->userRepository->update($user, $payload);

            if ($role instanceof Role) {
                $this->assignRole($updatedUser, $role);
            }

            return $updatedUser;
        });
    }

    public function deleteUser(User $user): void
    {
        if (Auth::id() === $user->id) {
            throw new RuntimeException('Anda tidak dapat menghapus akun sendiri.');
        }

        $this->database->transaction(function () use ($user) {
            $user->syncRoles([]);
            $this->userRepository->delete($user);
        });
    }

    public function blockUser(User $user, ?string $blockedUntil = null, ?string $reason = null): User
    {
        if (Auth::id() === $user->id) {
            throw new RuntimeException('Anda tidak dapat memblokir akun sendiri.');
        }

        return $this->database->transaction(function () use ($user, $blockedUntil, $reason) {
            return $this->userRepository->block($user, $blockedUntil, $reason);
        });
    }

    public function unblockUser(User $user): User
    {
        if (! $user->isBlocked()) {
            throw new RuntimeException('User tidak dalam status diblokir.');
        }

        return $this->database->transaction(function () use ($user) {
            return $this->userRepository->unblock($user);
        });
    }

    public function toggleStatus(User $user): User
    {
        if ($user->status === 'blocked') {
            throw new RuntimeException('Status pengguna saat ini diblokir dan tidak dapat diubah secara otomatis.');
        }

        $targetStatus = $user->status === 'active' ? 'inactive' : 'active';

        return $this->database->transaction(function () use ($user, $targetStatus) {
            return $this->userRepository->update($user, [
                'status' => $targetStatus,
                'blocked_until' => null,
                'blocked_reason' => null,
            ]);
        });
    }

    public function changePassword(User $user, array $data): void
    {
        $this->database->transaction(function () use ($user, $data) {
            $this->userRepository->update($user, [
                'password' => $data['password'],
                'password_changed_at' => now(),
            ]);
        });
    }

    public function getActiveUsers(array $filters = []): array
    {
        return $this->userRepository->getActive($filters)->toArray();
    }

    private function preparePayload(array $data, ?User $existing = null, bool $isNew = false, ?Role $role = null): array
    {
        $payload = [
            'name' => $data['name'] ?? $existing?->name,
            'username' => $data['username'] ?? $existing?->username,
            'email' => $data['email'] ?? $existing?->email,
            'phone' => $data['phone'] ?? $existing?->phone,
            'address' => $data['address'] ?? $existing?->address,
            'user_type' => $data['user_type'] ?? $existing?->user_type,
            'status' => $data['status'] ?? $existing?->status ?? 'active',
            'role_id' => $role?->id ?? $existing?->role_id,
            'profile_completed' => $data['profile_completed'] ?? $existing?->profile_completed ?? false,
            'profile_completed_at' => $data['profile_completed_at'] ?? $existing?->profile_completed_at,
            'blocked_until' => $data['blocked_until'] ?? $existing?->blocked_until,
            'blocked_reason' => $data['blocked_reason'] ?? $existing?->blocked_reason,
            'bio' => $data['bio'] ?? $existing?->bio,
        ];

        if ($isNew) {
            $payload['password_changed_at'] = now();
        }

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
            $payload['password_changed_at'] = now();
        }

        if (! in_array($payload['status'], ['active', 'inactive', 'blocked'], true)) {
            throw new RuntimeException('Status user tidak valid.');
        }

        if (! in_array($payload['user_type'], ['mahasiswa', 'staff'], true)) {
            throw new RuntimeException('Tipe user tidak valid.');
        }

        return $payload;
    }

    private function assignRole(User $user, Role $role): void
    {
        $user->forceFill([
            'role_id' => $role->id,
        ])->save();

        $user->syncRoles([$role->name]);
    }

    private function getValidRole(int $roleId): Role
    {
        $role = $this->roleRepository->findById($roleId);

        if (! $role || ! $role->is_active) {
            throw new RuntimeException('Role tidak valid atau tidak aktif.');
        }

        return $role;
    }
}
