<?php

namespace Modules\MarkingManagement\Observers;

use Illuminate\Support\Facades\Auth;
use Modules\MarkingManagement\Entities\Marking;
use Modules\MarkingManagement\Events\MarkingAuditLogged;

class MarkingObserver
{
    public function created(Marking $marking): void
    {
        $this->logAudit('created', $marking, $marking->getAttributes(), []);
    }

    public function updated(Marking $marking): void
    {
        $original = $marking->getOriginal();
        $current = $marking->getAttributes();

        // Determine specific action based on status change
        $action = $this->determineAction($original, $current);

        $this->logAudit($action, $marking, $current, $original);
    }

    public function deleted(Marking $marking): void
    {
        $original = $marking->getOriginal();

        $this->logAudit('deleted', $marking, [], $original);
    }

    /**
     * Determine specific action based on status changes
     */
    private function determineAction(array $original, array $current): string
    {
        $oldStatus = $original['status'] ?? null;
        $newStatus = $current['status'] ?? null;

        if ($oldStatus !== $newStatus) {
            return match ($newStatus) {
                Marking::STATUS_CANCELLED => 'cancelled',
                Marking::STATUS_EXPIRED => 'expired',
                Marking::STATUS_CONVERTED => 'converted',
                default => 'updated',
            };
        }

        // Check if expires_at changed (extension)
        if (isset($original['expires_at']) && isset($current['expires_at'])) {
            if ($current['expires_at'] > $original['expires_at']) {
                return 'extended';
            }
        }

        return 'updated';
    }

    private function logAudit(string $action, Marking $marking, array $attributes, array $original): void
    {
        MarkingAuditLogged::dispatch(
            $action,
            $marking,
            $attributes,
            $original,
            Auth::id(),
            Auth::check() ? get_class(Auth::user()) : null,
            'marking_observer'
        );
    }
}
