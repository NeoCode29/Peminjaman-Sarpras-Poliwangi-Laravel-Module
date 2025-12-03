<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Observers\PermissionObserver;
use App\Observers\RoleObserver;
use App\Observers\UserObserver;
use App\Repositories\AuthRepository;
use App\Repositories\Interfaces\AuthRepositoryInterface;
use App\Repositories\Interfaces\OAuthTokenRepositoryInterface;
use App\Repositories\Interfaces\PermissionRepositoryInterface;
use App\Repositories\Interfaces\RoleRepositoryInterface;
use App\Repositories\Interfaces\StaffEmployeeRepositoryInterface;
use App\Repositories\Interfaces\StudentRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\GlobalApproverRepositoryInterface;
use App\Repositories\OAuthTokenRepository;
use App\Repositories\PermissionRepository;
use App\Repositories\RoleRepository;
use App\Repositories\StaffEmployeeRepository;
use App\Repositories\StudentRepository;
use App\Repositories\UserRepository;
use App\Repositories\GlobalApproverRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->bind(PermissionRepositoryInterface::class, PermissionRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind(OAuthTokenRepositoryInterface::class, OAuthTokenRepository::class);
        $this->app->bind(StudentRepositoryInterface::class, StudentRepository::class);
        $this->app->bind(StaffEmployeeRepositoryInterface::class, StaffEmployeeRepository::class);
        $this->app->bind(\App\Repositories\Interfaces\NotificationRepositoryInterface::class, \App\Repositories\NotificationRepository::class);
        $this->app->bind(GlobalApproverRepositoryInterface::class, GlobalApproverRepository::class);

        // Notification services (singleton for better performance)
        $this->app->singleton(\App\Services\NotificationBuilder::class);
        $this->app->singleton(\App\Services\NotificationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Role::observe(RoleObserver::class);
        Permission::observe(PermissionObserver::class);
        User::observe(UserObserver::class);
    }
}
