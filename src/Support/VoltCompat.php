<?php

namespace Shipbytes\UiKit\Support;

use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

/**
 * Transition shim for apps upgrading from 0.2, aliased to Livewire\Volt\Volt
 * by UiKitServiceProvider when Volt is no longer installed. It keeps a
 * published 0.2 routes/auth.php (Volt::route(...)) booting until the app is
 * migrated; `php artisan ui-kit:doctor` flags the leftovers.
 *
 * Remove in 1.0.
 */
final class VoltCompat
{
    public static function route(string $uri, string $component): IlluminateRoute
    {
        return Route::livewire($uri, $component);
    }

    /**
     * @param  array<int, string>  $paths
     */
    public static function mount(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_dir($path)) {
                Livewire::addLocation(viewPath: $path);
            }
        }
    }
}
