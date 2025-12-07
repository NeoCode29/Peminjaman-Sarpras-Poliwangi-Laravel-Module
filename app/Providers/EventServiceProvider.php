<?php

namespace App\Providers;

use App\Events\KategoriSaranaAuditLogged;
use App\Events\PermissionAuditLogged;
use App\Events\RoleAuditLogged;
use App\Events\SaranaAuditLogged;
use App\Events\PrasaranaAuditLogged;
use App\Events\UserAuditLogged;
use App\Listeners\ClearPermissionCache;
use App\Listeners\SendPeminjamanStatusNotification;
use App\Listeners\StoreKategoriSaranaAudit;
use App\Listeners\StorePermissionAudit;
use App\Listeners\StoreRoleAudit;
use App\Listeners\StoreSaranaAudit;
use App\Listeners\StorePrasaranaAudit;
use App\Listeners\StoreUserAudit;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\MarkingManagement\Events\MarkingAuditLogged;
use Modules\MarkingManagement\Listeners\StoreMarkingAudit;
use Modules\PeminjamanManagement\Events\PeminjamanStatusChanged;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        KategoriSaranaAuditLogged::class => [
            StoreKategoriSaranaAudit::class,
        ],
        PermissionAuditLogged::class => [
            StorePermissionAudit::class,
        ],
        RoleAuditLogged::class => [
            StoreRoleAudit::class,
        ],
        SaranaAuditLogged::class => [
            StoreSaranaAudit::class,
        ],
        PrasaranaAuditLogged::class => [
            StorePrasaranaAudit::class,
        ],
        UserAuditLogged::class => [
            StoreUserAudit::class,
        ],
        MarkingAuditLogged::class => [
            StoreMarkingAudit::class,
        ],
        PeminjamanStatusChanged::class => [
            SendPeminjamanStatusNotification::class,
        ],
    ];

    protected $subscribe = [
        ClearPermissionCache::class,
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
