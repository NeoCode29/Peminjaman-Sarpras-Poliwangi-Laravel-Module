<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Role::class => RolePolicy::class,
        Permission::class => PermissionPolicy::class,
        User::class => UserPolicy::class,
        
        // Module Policies
        \Modules\SaranaManagement\Entities\Sarana::class => \Modules\SaranaManagement\Policies\SaranaPolicy::class,
        \Modules\SaranaManagement\Entities\KategoriSarana::class => \Modules\SaranaManagement\Policies\KategoriSaranaPolicy::class,
        \Modules\SaranaManagement\Entities\SaranaApprover::class => \Modules\SaranaManagement\Policies\SaranaApproverPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
