<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\AuthRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;

class AuthRepository implements AuthRepositoryInterface
{
    public function findByUsername(string $username): ?User
    {
        return User::where('username', $username)->first();
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findBySsoId(string $ssoId): ?User
    {
        return User::where('sso_id', $ssoId)->first();
    }

    public function create(array $attributes): User
    {
        return User::create($attributes);
    }

    public function update(User $user, array $attributes): User
    {
        $user->fill($attributes);
        $user->save();

        return $user->fresh();
    }

    public function updateLoginMetadata(User $user, array $metadata = []): User
    {
        $defaultMetadata = [
            'last_login_at' => now(),
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'login_count' => ($user->login_count ?? 0) + 1,
            'last_activity_at' => now(),
        ];

        $user->fill(Arr::only(array_merge($defaultMetadata, $metadata), [
            'last_login_at',
            'failed_login_attempts',
            'locked_until',
            'login_count',
            'last_activity_at',
        ]));

        $user->save();

        return $user->fresh();
    }

    public function resetFailedLoginAttempts(User $user): void
    {
        $user->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();
    }

    public function recordFailedLoginAttempt(User $user, int $maxAttempts, int $lockSeconds): void
    {
        $attempts = ($user->failed_login_attempts ?? 0) + 1;

        $payload = ['failed_login_attempts' => $attempts];

        if ($attempts >= $maxAttempts) {
            $payload['locked_until'] = now()->addSeconds($lockSeconds);
        }

        $user->forceFill($payload)->save();
    }
}
