<?php

namespace App\Observers;

use App\Events\UserAuditLogged;
use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserObserver
{
    public function created(User $user): void
    {
        UserCreated::dispatch($user);

        $this->logAudit('created', $user, $user->getAttributes(), []);
    }

    public function updated(User $user): void
    {
        $original = $user->getOriginal();
        $current = $user->getAttributes();

        UserUpdated::dispatch($user, [
            'before' => $original,
            'after' => $current,
        ]);

        $this->logAudit('updated', $user, $current, $original);
    }

    public function deleted(User $user): void
    {
        $original = $user->getOriginal();

        UserDeleted::dispatch(
            $user->id,
            $original['email'] ?? $user->email,
            Auth::id()
        );

        $this->logAudit('deleted', $user, [], $original);
    }

    private function logAudit(string $action, User $user, array $attributes, array $original): void
    {
        $attributes = $this->withoutSensitiveData($attributes);
        $original = $this->withoutSensitiveData($original);

        UserAuditLogged::dispatch(
            $action,
            $user,
            
            $attributes,
            $original,
            Auth::id(),
            Auth::check() ? get_class(Auth::user()) : null,
            'user_observer'
        );
    }

    private function withoutSensitiveData(array $data): array
    {
        unset($data['password'], $data['remember_token']);

        return $data;
    }
}
