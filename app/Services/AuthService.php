<?php

namespace App\Services;

use App\Events\UserAuditLogged;
use App\Models\User;
use App\Repositories\Interfaces\AuthRepositoryInterface;
use App\Repositories\Interfaces\RoleRepositoryInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    private const MAX_FAILED_ATTEMPTS = 5;
    private const LOCK_DURATION_SECONDS = 900; // 15 minutes

    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly DatabaseManager $database,
    ) {
    }

    /**
     * Attempt to authenticate the user using username or email.
     *
     * @return array{user: User, requires_profile_completion: bool}
     *
     * @throws AuthenticationException
     */
    public function login(string $identifier, string $password, bool $remember = false): array
    {
        $user = $this->findForAuthentication($identifier);

        if (! $user || ! Hash::check($password, $user->password)) {
            if ($user) {
                $this->authRepository->recordFailedLoginAttempt($user, self::MAX_FAILED_ATTEMPTS, self::LOCK_DURATION_SECONDS);
            }

            throw new AuthenticationException(__('auth.failed'));
        }

        if ($this->isAccountLocked($user)) {
            throw new AuthenticationException(__('auth.throttle', ['seconds' => $user->locked_until?->diffInSeconds(now()) ?? self::LOCK_DURATION_SECONDS]));
        }

        if (method_exists($user, 'isBlocked') && $user->isBlocked()) {
            throw new AuthenticationException(__('Account Anda diblokir. Silakan hubungi administrator.'));
        }

        $this->database->transaction(function () use (&$user, $remember) {
            Auth::login($user, $remember);

            $user = $this->authRepository->updateLoginMetadata($user, [
                'last_activity_at' => now(),
            ]);

            $this->authRepository->resetFailedLoginAttempts($user);

            if (! $user->roles()->exists()) {
                $this->assignDefaultRole($user);
            }

            $this->dispatchAuditEvent($user, 'auth.login', ['method' => 'password']);
        });

        $user = $user->fresh(['roles']);

        return [
            'user' => $user,
            'requires_profile_completion' => ! (bool) $user->profile_completed,
        ];
    }

    /**
     * Register a new user with sanitized data.
     */
    public function register(array $data): User
    {
        $payload = array_merge($data, [
            'password' => Hash::make($data['password']),
            'status' => 'active',
            'profile_completed' => false,
            'password_changed_at' => now(),
        ]);

        $user = $this->database->transaction(function () use ($payload) {
            $user = $this->authRepository->create($payload);

            $this->assignDefaultRole($user);

            $this->dispatchAuditEvent($user, 'auth.register', ['method' => 'password']);

            return $user->fresh(['roles']);
        });

        return $user;
    }

    /**
     * Finalize logout housekeeping, such as clearing other sessions.
     * @deprecated Use dispatchLogoutAudit instead (kept for backwards compatibility)
     */
    public function logout(User $user, string $currentSessionId, bool $invalidateOtherSessions = true): void
    {
        $this->database->transaction(function () use ($user, $currentSessionId, $invalidateOtherSessions) {
            if ($invalidateOtherSessions) {
                $this->database->table('sessions')
                    ->where('user_id', $user->getKey())
                    ->where('id', '!=', $currentSessionId)
                    ->delete();
            }

            $this->dispatchAuditEvent($user, 'auth.logout');
        });
    }

    /**
     * Dispatch logout audit event
     */
    public function dispatchLogoutAudit(User $user): void
    {
        $this->dispatchAuditEvent($user, 'auth.logout', [
            'method' => 'manual',
            'all_devices' => true,
        ]);
    }

    private function isAccountLocked(User $user): bool
    {
        // Jika tidak ada informasi locked_until, akun tidak dikunci
        if (! $user->locked_until) {
            return false;
        }

        // Jika waktu locked_until masih di masa depan, akun masih terkunci
        return $user->locked_until->isFuture();
    }

    private function assignDefaultRole(User $user): void
    {
        $roleName = match ($user->user_type) {
            'staff' => 'Peminjam Staff',
            'mahasiswa' => 'Peminjam Mahasiswa',
            default => 'Peminjam Mahasiswa',
        };

        $role = $this->roleRepository->findByName($roleName);

        if ($role) {
            $user->forceFill(['role_id' => $role->id])->save();
            $user->syncRoles([$role->name]);
        }
    }

    private function findForAuthentication(string $identifier): ?User
    {
        $user = $this->authRepository->findByUsername($identifier);

        if (! $user) {
            $user = $this->authRepository->findByEmail($identifier);
        }

        return $user;
    }

    private function dispatchAuditEvent(User $user, string $action, array $metadata = []): void
    {
        event(new UserAuditLogged(
            action: $action,
            user: $user,
            attributes: Arr::except($user->toArray(), ['password']),
            original: Arr::except($user->getOriginal(), ['password']),
            performedBy: $user->getKey(),
            performedByType: User::class,
            context: 'auth_service',
            metadata: $metadata,
        ));
    }
}
