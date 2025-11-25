<?php

namespace App\Listeners;

use App\Events\PermissionAuditLogged;
use App\Models\AuditLog;
use App\Models\Permission;
use Illuminate\Support\Arr;

class StorePermissionAudit
{
    public function handle(PermissionAuditLogged $event): void
    {
        $changes = $event->changes();

        AuditLog::create([
            'model_type' => Permission::class,
            'model_id' => $event->permission->getKey(),
            'action' => $event->action,
            'old_values' => $event->original,
            'new_values' => $event->attributes,
            'performed_by' => $event->performedBy,
            'performed_by_type' => $event->performedByType,
            'context' => $event->context ?? 'permission_observer',
            'metadata' => $this->buildMetadata($event, $changes),
            'performed_at' => now(),
        ]);
    }

    private function buildMetadata(PermissionAuditLogged $event, array $changes): array
    {
        $metadata = $event->metadata;

        if (! empty($changes)) {
            $metadata = array_merge(['changes' => $changes, 'changed_fields' => array_keys($changes)], $metadata);
        }

        return $metadata;
    }
}
