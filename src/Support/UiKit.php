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

    /**
     * A user's photo URL for ds::avatar's `src`, or null for the initials.
     * avatarUrl() is generated into the UiKitUser trait by the profile module,
     * so without that module every user gets initials.
     */
    public static function avatarUrl(?object $user): ?string
    {
        return $user !== null && method_exists($user, 'avatarUrl') ? $user->avatarUrl() : null;
    }

    /**
     * Whether a user holds admin access: the Spatie role when the
     * admin-middleware module put HasRoles on the model, else the is_admin
     * column the core fallback middleware reads. Either one counts, the same
     * as the two ways ui-kit:make-admin grants it.
     */
    public static function isAdmin(?object $user): bool
    {
        if ($user === null) {
            return false;
        }

        return (method_exists($user, 'hasRole') && $user->hasRole('admin'))
            || (bool) ($user->is_admin ?? false);
    }
}
