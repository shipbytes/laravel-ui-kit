<?php

namespace Shipbytes\UiKit\Tests;

use Illuminate\Filesystem\Filesystem;
use Livewire\LivewireServiceProvider;
use Livewire\Volt\VoltServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Shipbytes\UiKit\UiKitServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            VoltServiceProvider::class,
            UiKitServiceProvider::class,
        ];
    }

    /**
     * Remove everything an installer run publishes into the shared Testbench
     * skeleton so tests can't leak state into each other.
     */
    protected function cleanSkeleton(): void
    {
        $fs = new Filesystem;

        foreach ([
            config_path('ui-kit.php'),
            config_path('admin.php'),
            config_path('fortify.php'),
            base_path('routes/auth.php'),
            base_path('routes/admin.php'),
            base_path('routes/ui-kit-user.php'),
            resource_path('js/ui-kit.js'),
            resource_path('js/app.js'),
            resource_path('css/ui-kit.css'),
            resource_path('css/ui-kit-theme.css'),
            resource_path('css/app.css'),
            public_path('storage'),
        ] as $file) {
            if (is_link($file) || is_file($file)) {
                @unlink($file);
            }
        }

        foreach (glob(app_path('Models/*.php')) ?: [] as $file) {
            @unlink($file);
        }

        foreach ([
            app_path('Livewire'),
            app_path('Models/Concerns'),
            resource_path('views/layouts'),
            resource_path('views/components'),
            resource_path('views/livewire'),
        ] as $dir) {
            $fs->deleteDirectory($dir);
        }

        foreach ([
            'migrations/*_add_is_admin_to_users_table.php',
            'migrations/*_add_two_factor_columns_to_users_table.php',
            'migrations/*_create_passkeys_table.php',
            'migrations/2024_*.php',
        ] as $pattern) {
            foreach (glob(database_path($pattern)) ?: [] as $file) {
                @unlink($file);
            }
        }
    }
}
