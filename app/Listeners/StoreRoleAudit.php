<?php

namespace App\Listeners;

use App\Events\RoleAuditLogged;
use App\Models\AuditLog;
use App\Models\Role;

class StoreRoleAudit
{
    public function handle(RoleAuditLogged $event): void
    {
        AuditLog::create([
            'model_type' => Role::class,
            'model_id' => $event->role->getKey(),
            'action' => $event->action,
            'old_values' => $event->original,
            'new_values' => $event->attributes,
            'performed_by' => $event->performedBy,
            'performed_by_type' => $event->performedByType,
            'context' => $event->context ?? 'role_observer',
            'metadata' => $event->metadata + ['changed_fields' => array_keys($event->changes())],
            'performed_at' => now(),
        ]);
    }
}
