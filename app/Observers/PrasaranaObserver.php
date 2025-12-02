<?php

namespace App\Observers;

use App\Events\PrasaranaAuditLogged;
use Illuminate\Support\Facades\Auth;
use Modules\PrasaranaManagement\Entities\Prasarana;

class PrasaranaObserver
{
    public function created(Prasarana $prasarana): void
    {
        $this->logAudit('created', $prasarana, $prasarana->getAttributes(), []);
    }

    public function updated(Prasarana $prasarana): void
    {
        $original = $prasarana->getOriginal();
        $current = $prasarana->getAttributes();

        $this->logAudit('updated', $prasarana, $current, $original);
    }

    public function deleted(Prasarana $prasarana): void
    {
        $original = $prasarana->getOriginal();

        $this->logAudit('deleted', $prasarana, [], $original);
    }

    private function logAudit(string $action, Prasarana $prasarana, array $attributes, array $original): void
    {
        PrasaranaAuditLogged::dispatch(
            $action,
            $prasarana,
            $attributes,
            $original,
            Auth::id(),
            Auth::check() ? get_class(Auth::user()) : null,
            'prasarana_observer'
        );
    }
}
