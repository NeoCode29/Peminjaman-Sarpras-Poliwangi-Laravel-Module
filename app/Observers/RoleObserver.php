<?php

namespace App\Observers;

use App\Events\RoleAuditLogged;
use App\Events\RoleCreated;
use App\Events\RoleDeleted;
use App\Events\RoleUpdated;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;

class RoleObserver
{
    public function created(Role $role): void
    {
        RoleCreated::dispatch($role);

        $this->logAudit('created', $role, $role->getAttributes(), []);
    }

    public function updated(Role $role): void
    {
        $original = $role->getOriginal();
        $current = $role->getAttributes();

        RoleUpdated::dispatch($role, [
            'before' => $original,
            'after' => $current,
        ]);

        $this->logAudit('updated', $role, $current, $original);
    }

    public function deleted(Role $role): void
    {
        $original = $role->getOriginal();

        RoleDeleted::dispatch(
            $role->id,
            $original['name'] ?? $role->name,
            Auth::id()
        );

        $this->logAudit('deleted', $role, [], $original);
    }

    private function logAudit(string $action, Role $role, array $attributes, array $original): void
    {
        RoleAuditLogged::dispatch(
            $action,
            $role,
            $attributes,
            $original,
            Auth::id(),
            Auth::check() ? get_class(Auth::user()) : null,
            'role_observer'
        );
    }
}
