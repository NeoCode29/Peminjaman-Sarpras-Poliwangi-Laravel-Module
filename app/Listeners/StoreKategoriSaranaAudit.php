<?php

namespace App\Listeners;

use App\Events\KategoriSaranaAuditLogged;
use App\Models\AuditLog;
use Modules\SaranaManagement\Entities\KategoriSarana;

class StoreKategoriSaranaAudit
{
    public function handle(KategoriSaranaAuditLogged $event): void
    {
        $changes = $event->changes();

        AuditLog::create([
            'model_type' => KategoriSarana::class,
            'model_id' => $event->kategori->getKey(),
            'action' => $event->action,
            'old_values' => $event->original,
            'new_values' => $event->attributes,
            'performed_by' => $event->performedBy,
            'performed_by_type' => $event->performedByType,
            'context' => $event->context ?? 'kategori_sarana_observer',
            'metadata' => [
                'changes' => $changes,
                'changed_fields' => array_keys($changes),
            ] + $event->metadata,
            'performed_at' => now(),
        ]);
    }
}
