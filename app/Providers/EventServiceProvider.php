<?php

namespace App\Providers;

use App\Events\PermissionAuditLogged;
use App\Events\RoleAuditLogged;
use App\Events\UserAuditLogged;
use App\Listeners\ClearPermissionCache;
use App\Listeners\StorePermissionAudit;
use App\Listeners\StoreRoleAudit;
use App\Listeners\StoreUserAudit;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PermissionAuditLogged::class => [
            StorePermissionAudit::class,
        ],
        RoleAuditLogged::class => [
            StoreRoleAudit::class,
        ],
        UserAuditLogged::class => [
            StoreUserAudit::class,
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
