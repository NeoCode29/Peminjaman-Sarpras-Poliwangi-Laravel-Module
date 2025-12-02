<?php

namespace App\Listeners;

use App\Events\PrasaranaAuditLogged;
use App\Models\AuditLog;
use Modules\PrasaranaManagement\Entities\Prasarana;

class StorePrasaranaAudit
{
    public function handle(PrasaranaAuditLogged $event): void
    {
        $changes = $event->changes();

        AuditLog::create([
            'model_type' => Prasarana::class,
            'model_id' => $event->prasarana->getKey(),
            'action' => $event->action,
            'old_values' => $event->original,
            'new_values' => $event->attributes,
            'performed_by' => $event->performedBy,
            'performed_by_type' => $event->performedByType,
            'context' => $event->context ?? 'prasarana_observer',
            'metadata' => [
                'changes' => $changes,
                'changed_fields' => array_keys($changes),
            ] + $event->metadata,
            'performed_at' => now(),
        ]);
    }
}
