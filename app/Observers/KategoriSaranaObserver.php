<?php

namespace App\Observers;

use App\Events\KategoriSaranaAuditLogged;
use Illuminate\Support\Facades\Auth;
use Modules\SaranaManagement\Entities\KategoriSarana;

class KategoriSaranaObserver
{
    public function created(KategoriSarana $kategori): void
    {
        $this->logAudit('created', $kategori, $kategori->getAttributes(), []);
    }

    public function updated(KategoriSarana $kategori): void
    {
        $original = $kategori->getOriginal();
        $current = $kategori->getAttributes();

        $this->logAudit('updated', $kategori, $current, $original);
    }

    public function deleted(KategoriSarana $kategori): void
    {
        $original = $kategori->getOriginal();

        $this->logAudit('deleted', $kategori, [], $original);
    }

    private function logAudit(string $action, KategoriSarana $kategori, array $attributes, array $original): void
    {
        KategoriSaranaAuditLogged::dispatch(
            $action,
            $kategori,
            $attributes,
            $original,
            Auth::id(),
            Auth::check() ? get_class(Auth::user()) : null,
            'kategori_sarana_observer'
        );
    }
}
