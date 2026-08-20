<?php

namespace Shipbytes\UiKit\Support;

use Illuminate\Support\Facades\Route;

class UiKit
{
    /**
     * URL of the consumer's public home page.
     *
     * Uses the route named in ui-kit.brand.home_route when it exists; falls
     * back to the app root so fresh installs (which have no named "home"
     * route) never throw a RouteNotFoundException.
     */
    public static function homeUrl(): string
    {
        $route = config('ui-kit.brand.home_route');

        if (is_string($route) && $route !== '' && Route::has($route)) {
            return route($route, absolute: false);
        }

        return '/';
    }
}
