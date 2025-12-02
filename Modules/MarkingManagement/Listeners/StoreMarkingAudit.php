<?php

namespace Modules\MarkingManagement\Listeners;

use App\Models\AuditLog;
use Modules\MarkingManagement\Entities\Marking;
use Modules\MarkingManagement\Events\MarkingAuditLogged;

class StoreMarkingAudit
{
    public function handle(MarkingAuditLogged $event): void
    {
        $changes = $event->changes();

        AuditLog::create([
            'model_type' => Marking::class,
            'model_id' => $event->marking->getKey(),
            'action' => $event->action,
            'old_values' => $event->original,
            'new_values' => $event->attributes,
            'performed_by' => $event->performedBy,
            'performed_by_type' => $event->performedByType,
            'context' => $event->context ?? 'marking_observer',
            'metadata' => [
                'changes' => $changes,
                'changed_fields' => array_keys($changes),
                'event_name' => $event->marking->event_name ?? null,
                'status' => $event->marking->status ?? null,
            ] + $event->metadata,
            'performed_at' => now(),
        ]);
    }
}
