<?php

namespace Modules\PrasaranaManagement\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\PrasaranaManagement\Entities\Prasarana;
use App\Observers\PrasaranaObserver;

class PrasaranaManagementServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected $moduleName = 'PrasaranaManagement';

    /**
     * @var string $moduleNameLower
     */
    protected $moduleNameLower = 'prasaranamanagement';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));

        Prasarana::observe(PrasaranaObserver::class);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);

        // Bind Repository Interfaces to Implementations
        $this->app->bind(
            \Modules\PrasaranaManagement\Repositories\Interfaces\PrasaranaRepositoryInterface::class,
            \Modules\PrasaranaManagement\Repositories\PrasaranaRepository::class
        );

        $this->app->bind(
            \Modules\PrasaranaManagement\Repositories\Interfaces\KategoriPrasaranaRepositoryInterface::class,
            \Modules\PrasaranaManagement\Repositories\KategoriPrasaranaRepository::class
        );

        $this->app->bind(
            \Modules\PrasaranaManagement\Repositories\Interfaces\PrasaranaApproverRepositoryInterface::class,
            \Modules\PrasaranaManagement\Repositories\PrasaranaApproverRepository::class
        );

        // Register Services as Singletons
        $this->app->singleton(\Modules\PrasaranaManagement\Services\PrasaranaService::class);
        $this->app->singleton(\Modules\PrasaranaManagement\Services\KategoriPrasaranaService::class);
        $this->app->singleton(\Modules\PrasaranaManagement\Services\PrasaranaApproverService::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'), $this->moduleNameLower
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);

        $sourcePath = module_path($this->moduleName, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ], ['views', $this->moduleNameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (\Config::get('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }
        return $paths;
    }
}
