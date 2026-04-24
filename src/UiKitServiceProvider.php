<?php

namespace Shipbytes\UiKit;

use Shipbytes\UiKit\Console\InstallCommand;
use Shipbytes\UiKit\Console\InstallModuleCommand;
use Shipbytes\UiKit\Console\ListModulesCommand;
use Shipbytes\UiKit\Contracts\SidebarBadgeResolver;
use Shipbytes\UiKit\Support\ModuleRegistry;
use Shipbytes\UiKit\Support\NullBadgeResolver;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Volt\Volt;

class UiKitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../stubs/core/config/ui-kit.php', 'ui-kit');

        $this->app->singleton(ModuleRegistry::class);
        $this->app->bind(SidebarBadgeResolver::class, NullBadgeResolver::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../stubs/core/views', 'ui-kit');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                InstallModuleCommand::class,
                ListModulesCommand::class,
            ]);

            $this->registerPublishers();
        }

        $this->registerRoutes();
        $this->registerVoltMountPaths();
    }

    /**
     * Load the kit's published route files automatically so host apps don't
     * need to edit bootstrap/app.php. If a consumer wants to disable this
     * (e.g. to fully customize routing), just delete routes/auth.php or
     * routes/admin.php — the provider no-ops when they aren't present.
     */
    protected function registerRoutes(): void
    {
        $authRoutes = base_path('routes/auth.php');
        $adminRoutes = base_path('routes/admin.php');

        if (file_exists($authRoutes)) {
            Route::middleware('web')->group($authRoutes);
        }

        if (file_exists($adminRoutes)) {
            Route::middleware('web')->group($adminRoutes);
        }
    }

    protected function registerPublishers(): void
    {
        $core = __DIR__.'/../stubs/core';

        $this->publishes([
            $core.'/config/ui-kit.php' => config_path('ui-kit.php'),
            $core.'/config/admin.php' => config_path('admin.php'),
        ], 'ui-kit-config');

        $this->publishes([
            $core.'/views' => resource_path('views'),
        ], 'ui-kit-views');

        $this->publishes([
            $core.'/Livewire' => app_path('Livewire'),
        ], 'ui-kit-livewire');

        $this->publishes([
            $core.'/js/ui-kit.js' => resource_path('js/ui-kit.js'),
            $core.'/css/ui-kit.css' => resource_path('css/ui-kit.css'),
        ], 'ui-kit-assets');

        $this->publishes([
            $core.'/routes/auth.php' => base_path('routes/auth.php'),
            $core.'/routes/admin.php' => base_path('routes/admin.php'),
        ], 'ui-kit-routes');

        $this->publishes([
            $core.'/migrations/2024_01_01_000000_add_is_admin_to_users_table.php'
                => database_path('migrations/'.date('Y_m_d_His').'_add_is_admin_to_users_table.php'),
        ], 'ui-kit-migrations');
    }

    protected function registerVoltMountPaths(): void
    {
        $livewireDir = resource_path('views/livewire');

        if (is_dir($livewireDir) && class_exists(Volt::class)) {
            Volt::mount([$livewireDir]);
        }
    }
}
