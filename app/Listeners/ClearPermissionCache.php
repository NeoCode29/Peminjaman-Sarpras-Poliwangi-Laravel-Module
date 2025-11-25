<?php

namespace App\Listeners;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\PermissionRegistrar;

class ClearPermissionCache
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen([
            \App\Events\RoleCreated::class,
            \App\Events\RoleUpdated::class,
            \App\Events\RoleDeleted::class,
            \App\Events\PermissionCreated::class,
            \App\Events\PermissionUpdated::class,
            \App\Events\PermissionDeleted::class,
        ], [$this, 'handle']);
    }

    public function handle(): void
    {
        Cache::forget(config('permission.cache.key'));
        App::make(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
