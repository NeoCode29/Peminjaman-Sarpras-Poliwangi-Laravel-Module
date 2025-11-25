<?php

namespace App\Listeners;

use App\Events\UserAuditLogged;
use App\Models\AuditLog;
use App\Models\User;

class StoreUserAudit
{
    public function handle(UserAuditLogged $event): void
    {
        AuditLog::create([
            'model_type' => User::class,
            'model_id' => $event->user->getKey(),
            'action' => $event->action,
            'old_values' => $event->original,
            'new_values' => $event->attributes,
            'performed_by' => $event->performedBy,
            'performed_by_type' => $event->performedByType,
            'context' => $event->context ?? 'user_observer',
            'metadata' => $event->metadata + ['changed_fields' => array_keys($event->changes())],
            'performed_at' => now(),
        ]);
    }
}
