<?php

namespace App\Observers;

use App\Events\PermissionAuditLogged;
use App\Events\PermissionCreated;
use App\Events\PermissionDeleted;
use App\Events\PermissionUpdated;
use App\Models\Permission;
use Illuminate\Support\Facades\Auth;

class PermissionObserver
{
    public function created(Permission $permission): void
    {
        PermissionCreated::dispatch($permission);

        $this->logAudit('created', $permission, $permission->getAttributes(), []);
    }

    public function updated(Permission $permission): void
    {
        $original = $permission->getOriginal();
        $current = $permission->getAttributes();

        PermissionUpdated::dispatch($permission, [
            'before' => $original,
            'after' => $current,
        ]);

        $this->logAudit('updated', $permission, $current, $original);
    }

    public function deleted(Permission $permission): void
    {
        $original = $permission->getOriginal();

        PermissionDeleted::dispatch($permission->id, $original['name'] ?? $permission->name, Auth::id());

        $this->logAudit('deleted', $permission, [], $original);
    }

    private function logAudit(string $action, Permission $permission, array $attributes, array $original): void
    {
        PermissionAuditLogged::dispatch(
            $action,
            $permission,
            $attributes,
            $original,
            Auth::id(),
            Auth::check() ? get_class(Auth::user()) : null,
            'permission_observer'
        );
    }
}
