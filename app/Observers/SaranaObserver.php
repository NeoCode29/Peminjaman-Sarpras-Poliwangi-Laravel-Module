<?php

namespace App\Observers;

use App\Events\SaranaAuditLogged;
use Illuminate\Support\Facades\Auth;
use Modules\SaranaManagement\Entities\Sarana;

class SaranaObserver
{
    public function created(Sarana $sarana): void
    {
        $this->logAudit('created', $sarana, $sarana->getAttributes(), []);
    }

    public function updated(Sarana $sarana): void
    {
        $original = $sarana->getOriginal();
        $current = $sarana->getAttributes();

        $this->logAudit('updated', $sarana, $current, $original);
    }

    public function deleted(Sarana $sarana): void
    {
        $original = $sarana->getOriginal();

        $this->logAudit('deleted', $sarana, [], $original);
    }

    private function logAudit(string $action, Sarana $sarana, array $attributes, array $original): void
    {
        SaranaAuditLogged::dispatch(
            $action,
            $sarana,
            $attributes,
            $original,
            Auth::id(),
            Auth::check() ? get_class(Auth::user()) : null,
            'sarana_observer'
        );
    }
}
