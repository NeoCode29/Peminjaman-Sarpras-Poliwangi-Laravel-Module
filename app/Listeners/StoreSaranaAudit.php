<?php

namespace App\Listeners;

use App\Events\SaranaAuditLogged;
use App\Models\AuditLog;
use Modules\SaranaManagement\Entities\Sarana;

class StoreSaranaAudit
{
    public function handle(SaranaAuditLogged $event): void
    {
        $changes = $event->changes();

        AuditLog::create([
            'model_type' => Sarana::class,
            'model_id' => $event->sarana->getKey(),
            'action' => $event->action,
            'old_values' => $event->original,
            'new_values' => $event->attributes,
            'performed_by' => $event->performedBy,
            'performed_by_type' => $event->performedByType,
            'context' => $event->context ?? 'sarana_observer',
            'metadata' => [
                'changes' => $changes,
                'changed_fields' => array_keys($changes),
            ] + $event->metadata,
            'performed_at' => now(),
        ]);
    }
}
