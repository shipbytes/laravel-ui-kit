<?php

namespace Shipbytes\UiKit;

use Shipbytes\UiKit\Console\InstallCommand;
use Shipbytes\UiKit\Console\InstallModuleCommand;
use Shipbytes\UiKit\Console\ListModulesCommand;
use Shipbytes\UiKit\Contracts\SidebarBadgeResolver;
use Shipbytes\UiKit\Support\ModuleRegistry;
use Shipbytes\UiKit\Support\NullBadgeResolver;
use Shipbytes\UiKit\View\Components\UiKitBanners;
use Shipbytes\UiKit\View\Components\UiKitHead;
use Illuminate\Support\Facades\Blade;
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
        // Package-namespace views (ui-kit::…) live in resources/views and are
        // NOT published — consumers override them via views/vendor/ui-kit/.
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'ui-kit');

        Blade::component('ui-kit::head', UiKitHead::class);
        Blade::component('ui-kit::banners', UiKitBanners::class);

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
     * need to edit bootstrap/app.php.
     *
     * Only files carrying the "ui-kit:managed" header are loaded — a
     * routes/auth.php that predates the kit (Breeze, hand-rolled) is left
     * alone so it never gets registered twice. Consumers opt out by deleting
     * the header line (or the file).
     */
    protected function registerRoutes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        $files = [
            base_path('routes/auth.php'),
            base_path('routes/admin.php'),
            base_path('routes/ui-kit-user.php'),
        ];

        foreach ($files as $file) {
            if (! file_exists($file)) {
                continue;
            }

            $contents = (string) file_get_contents($file);

            if (! str_contains($contents, 'ui-kit:managed')) {
                continue;
            }

            Route::middleware('web')->group($file);
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
            $core.'/css/ui-kit-theme.css' => resource_path('css/ui-kit-theme.css'),
        ], 'ui-kit-assets');

        $this->publishes([
            $core.'/routes/auth.php' => base_path('routes/auth.php'),
            $core.'/routes/admin.php' => base_path('routes/admin.php'),
            $core.'/routes/ui-kit-user.php' => base_path('routes/ui-kit-user.php'),
        ], 'ui-kit-routes');

        $this->publishes([
            $core.'/migrations/2024_01_01_000000_add_is_admin_to_users_table.php'
                => $this->migrationTarget('add_is_admin_to_users_table'),
        ], 'ui-kit-migrations');
    }

    /**
     * Reuse the filename of an already-published copy of a kit migration so
     * repeated installs don't accumulate freshly-timestamped duplicates.
     */
    protected function migrationTarget(string $name): string
    {
        $existing = glob(database_path("migrations/*_{$name}.php")) ?: [];

        if ($existing !== []) {
            return $existing[0];
        }

        return database_path('migrations/'.date('Y_m_d_His')."_{$name}.php");
    }

    protected function registerVoltMountPaths(): void
    {
        $livewireDir = resource_path('views/livewire');

        if (is_dir($livewireDir) && class_exists(Volt::class)) {
            Volt::mount([$livewireDir]);
        }
    }
}
