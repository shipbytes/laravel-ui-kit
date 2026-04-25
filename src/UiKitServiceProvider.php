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

        $this->seedAnalyticsConfigDefaults();
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../stubs/core/views', 'ui-kit');

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
        $this->registerUtmMiddleware();
        $this->registerVoltMountPaths();
    }

    /**
     * Read GA4 / PostHog values from .env and inject them into config/services
     * at runtime, so consumers don't have to edit config/services.php.
     *
     * If a consumer has set the keys explicitly in services.php, those win —
     * we only fill in when the slot is empty.
     */
    protected function seedAnalyticsConfigDefaults(): void
    {
        $current = $this->app['config']->get('services', []);

        $current['google'] ??= [];
        $current['google']['analytics_id'] = $current['google']['analytics_id']
            ?? env('GOOGLE_ANALYTICS_ID');

        $current['posthog'] ??= [];
        $current['posthog']['public_key'] = $current['posthog']['public_key']
            ?? env('POSTHOG_PUBLIC_KEY');
        $current['posthog']['host'] = $current['posthog']['host']
            ?? env('POSTHOG_HOST', 'https://us.i.posthog.com');

        $this->app['config']->set('services', $current);
    }

    /**
     * Load the kit's published route files automatically so host apps don't
     * need to edit bootstrap/app.php. If a consumer wants to disable this
     * (e.g. to fully customize routing), just delete the relevant route file —
     * the provider no-ops when they aren't present.
     */
    protected function registerRoutes(): void
    {
        $files = [
            base_path('routes/auth.php'),
            base_path('routes/admin.php'),
            base_path('routes/ui-kit-user.php'),
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                Route::middleware('web')->group($file);
            }
        }
    }

    /**
     * If the analytics:utm provider was installed, push the kit's
     * CaptureUtmParameters middleware into the web group at runtime.
     * Removes the manual bootstrap/app.php edit step.
     */
    protected function registerUtmMiddleware(): void
    {
        $installed = config('ui-kit.installed_modules', []);
        $utmInstalled = in_array('utm', $installed['analytics'] ?? [], true);

        if (! $utmInstalled) {
            return;
        }

        $class = '\\App\\Http\\Middleware\\CaptureUtmParameters';

        if (! class_exists($class)) {
            return;
        }

        Route::pushMiddlewareToGroup('web', $class);
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
            $core.'/routes/ui-kit-user.php' => base_path('routes/ui-kit-user.php'),
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
