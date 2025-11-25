<?php

use App\Http\Middleware\EnsureProfileCompleted;
use App\Http\Middleware\EnsureUserNotBlocked;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => \Spatie\Permission\Middlewares\PermissionMiddleware::class,
            'role' => \Spatie\Permission\Middlewares\RoleMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class,
            'profile.completed' => EnsureProfileCompleted::class,
            'user.not.blocked' => EnsureUserNotBlocked::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
